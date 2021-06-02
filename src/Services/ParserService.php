<?php

namespace Services;

use Entities\Agency;
use Entities\Log;
use Entities\Region;
use GuzzleHttp\Client;
use PHPHtmlParser\Dom;
use PhpQuery\PhpQuery;
use Repositories\AgencyRepository;
use Repositories\LogRepository;
use Repositories\RegionRepository;
use voku\helper\HtmlDomParser;

class ParserService
{
    private const BASE_URI = 'https://fsin-pokupka.ru/';
    private const STATUS_SUCCESS = 'SUCCESS';
    private Client $client;
    private LogRepository $logRepository;
    private RegionRepository $regionRepository;
    private AgencyRepository $agencyRepository;

    public function __construct(
        LogRepository $logRepository,
        RegionRepository $regionRepository,
        AgencyRepository $agencyRepository
    ) {
        $this->client = new Client(['base_uri' => self::BASE_URI]);
        $this->logRepository = $logRepository;
        $this->regionRepository = $regionRepository;
        $this->agencyRepository = $agencyRepository;
    }

    /**
     * @throws \Exception
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
        //Passing in the "body" request option as an array to send a request is not supported.
        // Please use the "form_params" request option to send a application/x-www-form-urlencoded request,
        // or the "multipart" request option to send a multipart/form-data request.
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
                foreach ($this->agencyRepository->getAll() as $agency) {
                    $this->parseProductsByAgency($agency);
                }
            }
        } catch (\Throwable $e) {
            $log = new Log($e->getMessage());
            $this->logRepository->persist($log);
            $this->logRepository->flush();
        }
    }

    private function parseProductsByAgency(Agency $agency)
    {

    }
}