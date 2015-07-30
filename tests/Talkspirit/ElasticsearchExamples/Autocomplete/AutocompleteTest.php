<?php

namespace Talkspirit\ElasticsearchExamples;

use Elastica\Client;
use Elastica\Connection;
use Elastica\Index;
use Elastica\Document;
use Elastica\Type\Mapping;
use Symfony\Component\Yaml\Yaml;

class AutocompleteTest extends \PHPUnit_Framework_TestCase
{
    private $index;

    public function setup()
    {
        $filename = __DIR__ . '/../../../fixtures/config.yml';
        $config = Yaml::parse(file_get_contents($filename));
        $client = new Client();
        $indexParams = array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 1));
        $index = $client->getIndex($config['elastica']['indexes']['index']['index_name']);
        $index->create($indexParams, true);
        sleep(1);
        $index->close();
        $index->setSettings(['index' => $config['elastica']['settings']]);
        $index->open();

        foreach ($config['elastica']['indexes']['index']['types'] as $type => $settings) {
            $mapping = new Mapping($index->getType($type), $settings['mappings']);
            $mapping->send();
        }
        $this->index = $index;
    }

    private function search($query)
    {
        return $this->index->search(json_decode($query, true));
    }
    /**
     * @test
     */
    public function fill_different_queries()
    {
        $index = $this->index;
        $type = $index->getType('user');
        $type->addDocument(new Document(1, array(
            'username' => 'olivier',
            'displayname' => 'Olivier Doe',
            'autocomplete' => 'olivier olivier doe',
            'status' => 1,
            'groups' => ['id' => 'group1', 'id' => 'group2']
        )));
        $type->addDocument(new Document(2, array(
            'username' => 'jeremie',
            'displayname' => 'Jérémie Doe',
            'autocomplete' => 'jeremie jérémie doe',
            'status' => 1,
            'groups' => ['id' => 'group2']
        )));
        $index->refresh();

        $query = '{"query":{"term":{"autocomplete":"ol"}}}';
        /** @var Elastica\ResultSet */
        $resultSet = $this->search($query);
        $this->assertEquals(1, $resultSet->getTotalHits(), $query);

        $query = '{"query":{"term":{"autocomplete":"j"}}}';
        /** @var Elastica\ResultSet */
        $resultSet = $this->search($query);
        $this->assertEquals(1, $resultSet->getTotalHits(), $query);

        $query = '{"query":{"term":{"autocomplete":"je"}}}';
        /** @var Elastica\ResultSet */
        $resultSet = $this->search($query);
        $this->assertEquals(1, $resultSet->getTotalHits(), $query);

        // todo the term should be found
        $query = '{"query":{"term":{"autocomplete":"jé"}}}';
        /** @var Elastica\ResultSet */
        $resultSet = $this->search($query);
        $this->assertEquals(0, $resultSet->getTotalHits(), $query);
    }
}
