<?php

namespace Services;

use dto\ProductDto;
use Entities\Agency;
use Entities\Log;
use Entities\Product;
use Entities\Region;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\Cookie\SetCookie;
use PHPHtmlParser\Dom;
use Repositories\AgencyRepository;
use Repositories\LogRepository;
use Repositories\ProductRepository;
use Repositories\RegionRepository;

class ParserService
{
    private const SITE_LOGIN = 'parser';
    private const SITE_PASSWORD = 'fsin-pokupka.ru';
    private const BASE_URI = 'https://fsin-pokupka.ru';
    private const DOMAIN = 'fsin-pokupka.ru';
    private const STATUS_SUCCESS = 'SUCCESS';
    private Client $client;
    private LogRepository $logRepository;
    private RegionRepository $regionRepository;
    private AgencyRepository $agencyRepository;
    private ProductRepository $productRepository;
    private LogService $logService;

    public function __construct(
        LogService $logService,
        LogRepository $logRepository,
        RegionRepository $regionRepository,
        AgencyRepository $agencyRepository,
        ProductRepository $productRepository
    ) {
        $jar = new SessionCookieJar('PHPSESSID', true);
        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'cookies' => $jar,
        ]);
        $this->logRepository = $logRepository;
        $this->regionRepository = $regionRepository;
        $this->agencyRepository = $agencyRepository;
        $this->productRepository = $productRepository;
        $this->logService = $logService;
    }

    /**
     * @throws \Exception
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function parseRegions()
    {
        $dom = new Dom();
        $dom->loadFromUrl(self::BASE_URI);

        $elements = $dom->find('[data-regionid]');
        if(!$elements) {
            throw new \Exception('Region parse failed');
        }

        foreach ($elements as $element) {
            $externalId = (int) $element->getAttribute('data-regionid');
            $name = $element->text();
            $region = $this->regionRepository->findOneByExternalId($externalId)
                ?: Region::create($name, $externalId);
            $this->regionRepository->persist($region);
        }

        $this->regionRepository->flush();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function parseAgenciesByRegion(Region $region)
    {
        $options = [
            'form_params' => [
                'ACTION' => 'SET_REGION',
                'ELEMENT_ID' => $region->getExternalId(),
            ],
        ];
        $response = $this->client->request(
            'POST',
            'local/components/litegroup/store.select/ajax.php',
            $options
        );
        $responseData = json_decode($response->getBody(), true);
        $status = $responseData['STATUS'] ?? null;
        $items = $responseData['DATA']['ELEMENT_LIST'] ?? null;
        if($status != self::STATUS_SUCCESS || !$items) {
            $externalId = $region->getExternalId();
            $description = $responseData['DESCRIPTION'] ?? null;
            throw new \Exception(
                "Agencies parse failed by region external id {$externalId} DESCRIPTION: {$description}"
            );
        }

        foreach ($items as $item) {
            $agency = $this->agencyRepository->findOneByExternalId($item['id'])
                ?: Agency::create($region, $item['value'], $item['id']);
            $this->agencyRepository->persist($agency);
        }

        $this->agencyRepository->flush();
    }

    public function parse()
    {
        try {
            $log = $this->logRepository->findLastOneSinceDt(new \DateTime('yesterday'));
            if ($log && $log->isSuccess()) {
                return;
            }

            $this->parseRegions();
            foreach ($this->regionRepository->getAll() as $region) {
                $this->parseAgenciesByRegion($region);
            }
            foreach ($this->agencyRepository->getAll() as $agency) {
                $this->parseProductsByAgency($agency);
            }
            $this->logService->log();
        } catch (\Throwable $e) {
            $this->logService->log($e);
        }
    }

    /**
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \PHPHtmlParser\Exceptions\UnknownChildTypeException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \Exception
     */
    private function parseProductsByAgency(Agency $agency)
    {
        $options = [
            'form_params' => [
                'ACTION' => 'SET_AGENCY',
                'ELEMENT_ID' => $agency->getExternalId(),
            ],
        ];
        $response = $this->client->request(
            'POST',
            'local/components/litegroup/store.select/ajax.php',
            $options
        );
        $responseData = json_decode($response->getBody(), true);
        $status = $responseData['STATUS'] ?? null;

        if($status != self::STATUS_SUCCESS) {
            $description = $responseData['DESCRIPTION'] ?? null;
            $externalId = $agency->getExternalId();
            throw new \Exception(
                "Products parse failed by agency external id {$externalId} DESCRIPTION: {$description}"
            );
        }
        $cookieJar = $this->applyOferta($agency);

        $url = '/catalog/';
        $html = $this->getHtmlContentByCookieAndUrls($cookieJar, [$url])[$url] ?? null;
        if ($html) {
            try {
                $dom = new Dom();
                $dom->loadStr($html);
            } catch (\Throwable $e) {
                $this->logService->log($e);
                return;
            }

            $lastPage = $this->getLastPageNumber($dom);

            $urls = [];
            for ($page = 1; $page <= $lastPage; $page++) {
                $urls[] = '/catalog/?PAGEN_1=' . $page;
            }

            $htmlPages = $this->getHtmlContentByCookieAndUrls($cookieJar, $urls);
            foreach ($htmlPages as $htmlPage) {
                $dom = new Dom();
                $dom->loadStr($htmlPage);
                /** @var Dom\Node\HtmlNode[] $itemNodes */
                $itemNodes = $dom->find('.catalog-item-info');
                $productDtos = [];
                foreach ($itemNodes as $itemNode) {
                    $titleNode = $itemNode->find('.item-title')[0];
                    /** @var Dom\Node\HtmlNode $quantityNode */
                    $quantityNode = $itemNode->find('.quantity-available')[0];

                    $count = (int) preg_replace('/[^0-9]/', '', $quantityNode->innerText());
                    $productDto = new ProductDto();
                    $productDto->name = $titleNode->getAttribute('title');
                    $productDto->href = $titleNode->getAttribute('href');
                    $productDto->balance = $count;
                    $productDto->externalAgencyId = $agency->getExternalId();
                    $productDtos[$productDto->href] = $productDto;
                }
                $this->parseProducts($productDtos, $cookieJar);
            }

        }

    }

    private function applyOferta(Agency $agency): CookieJar
    {
        $id = $agency->getExternalId();
        $cookieJar = CookieJar::fromArray([
            'BITRIX_SM_AGENCY' => $id,
            "BITRIX_SM_OFERTA_HAS_SHOP_{$id}" => 'Y',
        ], self::DOMAIN);
        $options = [
            'form_params' => [
                'check_oferta' => 'HAS_SHOP',
            ],
            'cookies' => $cookieJar,
        ];

        $response = $this->client->request(
            'POST',
            'ajax/apply_oferta.php',
            $options
        );

        $cookies = $response->getHeader('set-cookie');
        $cookiesData = [
            'BITRIX_SM_AGENCY' => $id,
        ];
        foreach ($cookies as $cookie) {
            $setCookie = SetCookie::fromString($cookie);
            $name = $setCookie->getName();
            $value = $setCookie->getValue();
            $cookiesData[$name] = $value;
        }

        return CookieJar::fromArray($cookiesData, self::DOMAIN);
    }

    private function getHtmlContentByCookieAndUrls(CookieJar $cookieJar, $urls = ['/catalog/']): array
    {

        $mh = curl_multi_init();
        $chs = [];
        $headers = $this->getHeaders($cookieJar);
        foreach ($urls as $url) {
            $chs[$url] = $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::BASE_URI . $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_multi_add_handle($mh, $ch);
        }

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);
        $result = [];
        foreach ($chs as $url => $ch) {
            $result[$url] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
        }

        curl_multi_close($mh);

        return $result;
    }

    /**
     * @param ProductDto[] $productDtos
     * @param CookieJar $cookieJar
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     * @throws \PHPHtmlParser\Exceptions\UnknownChildTypeException
     * @throws \Exception
     */
    private function parseProducts(array $productDtos, CookieJar $cookieJar)
    {
        $urls = [];
        foreach ($productDtos as $productDto) {
            $urls[] = $productDto->href;
        }
        $htmlProducts = $this->getHtmlContentByCookieAndUrls($cookieJar, $urls);
        foreach ($htmlProducts as $href => $htmlProduct) {
            try {
                $dom = new Dom();
                $dom->loadStr($htmlProduct);
            } catch (\Throwable $e) {
                $this->logService->log($e);
                continue;
            }

            /** @var Dom\Node\HtmlNode $item */
            $item = $dom->find('.catalog-detail-element')[0];
            if (!$item) {
                continue;
            }
            $productDto = $productDtos[$href];
            $productDto->externalId = explode('_', $item->getAttribute('id'))[2];
            $product = $this->productRepository->findOneByExternalId($productDto->externalId);
            if (!$product) {
                /** @var Dom\Node\HtmlNode[] $properties */
                $properties = $item->find('.catalog-detail-property');
                foreach ($properties as $property) {
                    /** @var Dom\Node\HtmlNode $name */
                    $name = $property->find('.name')[0];
                    if ($name->innerText() === 'Производитель') {
                        /** @var Dom\Node\HtmlNode $value */
                        $value = $property->find('.val')[0];
                        $productDto->vendor = $value->innerText();
                    }
                    if ($name->innerText() === 'Поставщик') {
                        /** @var Dom\Node\HtmlNode $value */
                        $value = $property->find('.val')[0];
                        $productDto->supplier = $value->innerText();
                    }
                }
                $agency = $this->agencyRepository->getByExternalId($productDto->externalAgencyId);
                $product = Product::create(
                    $agency,
                    $productDto->name,
                    $productDto->externalId,
                    $productDto->balance,
                    $productDto->vendor,
                    $productDto->supplier
                );
            }

            $product->setBalance($productDto->balance);

            $this->productRepository->persist($product);
            $this->logService->printProductMessage($product);
        }

        $this->productRepository->flush();
    }

    /**
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\UnknownChildTypeException
     */
    private function getLastPageNumber(Dom $dom): int
    {
        $lastPage = 1;
        $pagination = $dom->find('.pagination');
        if ($pagination && $pagination->count()) {
            /** @var Dom\Node\HtmlNode $node */
            $node = $pagination[0];
            /** @var Dom\Node\HtmlNode[] $aNodes */
            $aNodes = $node->find('a');

            foreach ($aNodes as $aNode) {
                $number = (int) $aNode->innerText();
                $lastPage = max($lastPage, $number);
            }
        }

        return $lastPage;
    }

    private function getHeaders(CookieJar $cookieJar): array
    {
        $headers = [];
        $headers[] = 'Authority: fsin-pokupka.ru';
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Sec-Ch-Ua: \" Not;A Brand\";v=\"99\", \"Google Chrome\";v=\"91\", \"Chromium\";v=\"91\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36';
        $headers[] = 'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8';
        $headers[] = 'Sec-Fetch-Site: same-origin';
        $headers[] = 'Sec-Fetch-Mode: no-cors';
        $headers[] = 'Sec-Fetch-User: ?1';
        $headers[] = 'Sec-Fetch-Dest: image';
        $headers[] = 'Referer: https://fsin-pokupka.ru/catalog/';
        $headers[] = 'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
        $headers[] = 'Origin: https://fsin-pokupka.ru';
        $headers[] = 'Pragma: no-cache';
        $headers[] = 'X-Client-Data: CJO2yQEIo7bJAQipncoBCNGgygEIoKDLAQjd8ssB';
        $cookie = [];
        foreach ($cookieJar->toArray() as $item) {
            $key = $item['Name'];
            $value = $item['Value'];
            $cookie[] = "$key=$value";
        }
        $headers[] = "Cookie: " . implode('; ', $cookie);

        return $headers;
    }
}