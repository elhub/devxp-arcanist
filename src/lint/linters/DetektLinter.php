<?php

/*
 * Modified from DetektLinter of http://github.com/moneybird/arc-detekt-linter
 *
 * Copyright (C) 2018 Moneybird
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Author: Robbin Voortman (robbin@moneybird.com)
 * Date: 20-02-2018
 */
final class DetektLinter extends ArcanistExternalLinter {
  private $outputName = 'result';

  public function getInfoName() {
    return 'Detekt Linter';
  }

  public function getInfoURI() {
    return 'https://github.com/arthurbosch/detekt';
  }

  public function getInfoDescription() {
    return 'Detekt linter for Kotlin';
  }

  public function getLinterName() {
    return 'DETEKT';
  }

  public function getLinterConfigurationName() {
    return 'detekt';
  }

  public function getDefaultBinary() {
    return 'detekt';
  }

  public function getInstallInstructions() {
    return 'Use the devxp-linux script to install this linter.';
  }

  public function shouldExpectCommandErrors() {
    return true;
  }

  protected function getMandatoryFlags() {
    return array(
      '--report',
      'xml:'.dirname(__FILE__).'/'.$this->outputName.'.xml',
      '--input',
    );
  }

  protected function getDefaultFlags() {
    return array();
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $file = dirname(__FILE__).'/'.$this->outputName.'.xml';
    if (!file_exists($file)) { return []; }

    $xml_string = file_get_contents($file);
    $xml = simplexml_load_string($xml_string)->{'file'};

    $violations = $xml->{'error'};
    if ($violations === null) { return []; }

    $messages = [];

    foreach ($violations as $error) {
      $violation          = $this->parseViolation($error);
      $violation['path']  = $path;
      $messages[]         = ArcanistLintMessage::newFromDictionary($violation);
    }

    $this->cleanGeneratedFiles();

    return $messages;
  }

  private function parseViolation(SimpleXMLElement $xml) {
      return array(
          'code'        => $this->getLinterName(),
          'name'        => (string)str_replace('detekt.', '', $xml['source']),
          'line'        => (int)$xml['line'],
          'char'        => (int)$xml['column'],
          'severity'    => $this->getArcanistSeverity((string)$xml['severity']),
          'description' => (string)$xml['message'],
      );
  }

  /**
   * Clean up the generated files from Detekt
   */
  private function cleanGeneratedFiles() {
    $generated_extensions = ['html', 'txt', 'xml'];
    foreach ($generated_extensions as $extension) {
      $file_path = dirname(__FILE__).'/'.$this->outputName.'.'.$extension;
      if (file_exists($file_path)) {
        unlink($file_path);
      }
    }
  }

  /**
   * Check if a path exists, here or one level higher.
   */
  private function pathExist($path) {
    $working_copy = $this->getEngine()->getWorkingCopy();
    $root = $working_copy->getProjectRoot();

    if (Filesystem::pathExists($path)) {
      return true;
    }

    $path = Filesystem::resolvePath($path, $root);
    if (Filesystem::pathExists($path)) {
      return true;
    }
    return false;
  }

  /**
   * Match the severity string to an ArcanistLintSeverity
   */
  private function getArcanistSeverity($severity_name) {
      $map = array(
          'error' => ArcanistLintSeverity::SEVERITY_ERROR,
          'warning' => ArcanistLintSeverity::SEVERITY_WARNING,
          'info' => ArcanistLintSeverity::SEVERITY_ADVICE,
      );
      foreach ($map as $name => $severity) {
          if ($severity_name == $name) {
              return $severity;
          }
      }
      return ArcanistLintSeverity::SEVERITY_WARNING;
  }
}
