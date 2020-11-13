<?php

/**
 * Lints Kotlin source files using the "detekt" tool.
 */
final class YamlLinter extends ArcanistExternalLinter {

  private $config = null;

  public function getInfoName() {
    return 'Yamllint';
  }

  public function getInfoDescription() {
    return pht('Checks Yaml files');
  }

  public function getInfoURI() {
    return 'https://github.com/adrienverge/yamllint';
  }

  public function getLinterName() {
    return 'yamllint';
  }

  public function getLinterConfigurationName() {
    return 'yamllint';
  }

  public function getDefaultBinary() {
    return 'yamllint';
  }

  public function getVersion() {
    return false;
  }

  public function getInstallInstructions() {
    return pht('Use the dev-tools script to install this linter.');
  }

  protected function getMandatoryFlags() {
    return array('-f=parsable');
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $lines = phutil_split_lines($stdout, false);

    // install.yml:2:7: [error] syntax error: mapping values are not allowed here
    $regex = '/^(?P<path>.*):(?P<line>\d+):(?P<char>\d+): \[(?P<severity>.*)\] (?P<type>.*): (?P<message>.*)$/';
    $messages = array();
    foreach ($lines as $line) {
      $matches = null;
      if (preg_match($regex, $line, $matches)) {
        $message = new ArcanistLintMessage();
        $message->setPath($path);
        $message->setLine($matches['line']);
        if (!empty($matches['char'])) {
          $message->setChar($matches['char']);
        }
        $message->setCode($matches['type']);
        $message->setName($this->getLinterName());
        $message->setDescription($matches['message']);
        $message->setSeverity($this->getMatchSeverity($matches['severity']));
        $messages[] = $message;
      }
    }

    return $messages;
  }

  private function getMatchSeverity($name) {
    $map = array(
      'error' => ArcanistLintSeverity::SEVERITY_ERROR,
      'warn'  => ArcanistLintSeverity::SEVERITY_WARNING,
    );

    if (array_key_exists($name, $map)) {
       return $map[$name];
    }

    return ArcanistLintSeverity::SEVERITY_ERROR;
  }
}
