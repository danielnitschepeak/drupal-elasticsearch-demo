<?php

namespace Drupal\indexjobs\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\node\Entity\Node;
use Elasticsearch;

/**
 * Class IndexJobsCommand.
 *
 * @package Drupal\indexjobs
 */
class IndexJobsCommand extends Command {

  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('indexjobs')
      ->setDescription($this->trans('commands.indexjobs.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    $clientBuilder = Elasticsearch\ClientBuilder::create();
    $client = $clientBuilder->build();

    try {
      foreach ($this->getAllJobIds() as $jobId) {
        $job = Node::load($jobId);
        $io->info('Indexing: ' . $job->get('title')->value);
        $data = [
          'index' => 'jobs',
          'type' => 'job',
          'body' => [
            'nid' => (string) $job->id(),
            'title' => $job->get('title')->value,
            'employer' => $job->get('field_employer')->value,
            'closing_date' => $job->get('field_closing_date')->value,
            'description' => $job->get('field_description')->value,
          ],
        ];
        $response = $client->index($data);
      }
    }
    catch (\Exception $e) {
      $io->error('Error while indexing: ' . $e->getMessage());
    }

//    $data = [
//      'index' => 'jobs',
//      'type' => 'job',
//      'body' => [
//        "title" => "CEO",
//	      "description" => "For this amazing job, you must be 18 months old or younger with 5 years experience.",
//	      "employer" => "EvilC0rp"
//      ],
//    ];
  }

  private function getAllJobIds() {
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'job');
    return $query->execute();
  }
}
