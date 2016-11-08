<?php

namespace Drupal\importjobs\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\node\Entity\Node;
use TesseractOCR;
//use Imagick;

/**
 * Class ImportJobsCommand.
 *
 * @package Drupal\importjobs
 */
class ImportJobsCommand extends Command {

  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('importjobs')
      ->setDescription($this->trans('commands.importjobs.description'))
      ->addArgument('jobspath', InputArgument::REQUIRED, 'Import jobs from HTML files into Drupal');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    $jobsPath = $input->getArgument('jobspath');

    $htmlFiles = glob($jobsPath . '/*.html');

    $html = new \DOMDocument();

    foreach ($htmlFiles as $htmlFile) {
      if (!$html->loadHTMLFile($htmlFile)) {
        $io->info("Could not load html file $htmlFile");
      }

      $xpath = new \DomXPath($html);

      $jobTitle = trim($html->getElementsByTagName('h3')->item(0)->textContent);
      $jobRefNo = $xpath->query("//span[contains(., 'Job Ref No.')]");
      $jobRefNo = preg_replace('/[^0-9]/', '', $jobRefNo->item(0)->textContent);
      $closingDate = $xpath->query("//span[contains(., 'Closing Date:')]");
      $closingDate = preg_replace('/[^0-9\/]/', '', $closingDate->item(0)->textContent);
      $closingDate = date_create_from_format('d/m/Y', $closingDate, new \DateTimeZone('Asia/Colombo'));
      $closingDate = $closingDate->format('Y-m-d');
      $employer = $xpath->query("//span[@class='emp_style2']");
      $employer = $employer->item(0)->textContent;
      $imagePaths = $xpath->query("//img[contains(@src,'/logo')]");
      $imageText = NULL;
      foreach ($imagePaths as $path) {
        if (strpos($path->getAttribute('src'), 'jpg') !== FALSE) {
          $imagePath = $path->getAttribute('src');
          $imageName = str_replace(['/', ' '], '_', $imagePath);
          // if (TRUE == $this->prepareImageForOCR($jobsPath . '/' . $imageName, $imageData)) {
          $imageText = new TesseractOCR($jobsPath . '/' . $imageName);
          $imageText = $imageText->recognize();
          break;
        }
      }

      if (NULL !== $imageText) {
        $io->info('Creating job: "' . $jobTitle . '"');
        $result = $this->createJobNode($jobTitle, $imageText, $jobRefNo, $closingDate, $employer);
        if (!$result) {
          $io->info('Job create failed');
        }
      }
      else {
        $io->info('Skipping job due to missing image text');
      }

    }
  }

  private function createJobNode($title, $description, $jobRefNo, $closingDate, $employer) {
    $node = Node::create([
      'type'  => 'job',
      'title' => $title,
      'field_job_reference_number' => $jobRefNo,
      'field_closing_date' => [
        'value' => $closingDate,
      ],
      'field_employer' => $employer,
      'field_description' => [
        'value' => nl2br($description),
        'format' => 'full_html',
      ],
    ]);
    return $node->save();
  }

  private function prepareImageForOCR($imagePath) {
    try {
      $image = new \Imagick($imagePath);
      $height = $image->getImageHeight();
      $width = $image->getImageWidth();
      $image->resizeImage($width * 2, $height * 2, Imagick::FILTER_LANCZOS, 0.9, true);
      $image->modulateImage(100, 0, 100);
      $image->adaptiveSharpenImage(0, 1);
      $image->writeImage($imagePath);
    }
    catch (\ImagickException $e) {
      return FALSE;
    }

    return TRUE;
  }
}
