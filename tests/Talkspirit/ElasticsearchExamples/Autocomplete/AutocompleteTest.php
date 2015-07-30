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
        $indexParams = array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0));
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
        $data = json_decode($query, true);
        $this->assertTrue(is_array($data), "$query is not a json valid string");
        return $this->index->search($data);
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

        $type->addDocument(new Document(3, array(
            'username' => 'youssef',
            'displayname' => 'Youssef Doe',
            'autocomplete' => 'youssef doe',
            'status' => 1,
            'groups' => ['id' => 'group1']
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

        $query = '{"query":{"term":{"autocomplete":"jé"}}}';
        /** @var Elastica\ResultSet */
        $resultSet = $this->search($query);
        $this->assertEquals(1, $resultSet->getTotalHits(), $query);

        $query = '{"query":{"term":{"autocomplete":"y"}}}';
        /** @var Elastica\ResultSet */
        $resultSet = $this->search($query);
        $this->assertEquals(1, $resultSet->getTotalHits(), $query);

        $query = '{"query":{"term":{"autocomplete":"you"}}}';
        /** @var Elastica\ResultSet */
        $resultSet = $this->search($query);
        $this->assertEquals(1, $resultSet->getTotalHits(), $query);

        $query = '{"query":{"term":{"autocomplete":"oussef"}}}';
        /** @var Elastica\ResultSet */
        $resultSet = $this->search($query);
        $this->assertEquals(1, $resultSet->getTotalHits(), $query);

    }
}
