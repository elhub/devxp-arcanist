<?php

final class JestUnitTestEngine extends ArcanistUnitTestEngine {

  private $command;
  private $affectedTests = array();
  private $projectRoot;

  public function getEngineConfigurationName() {
    return 'jest';
  }

  public function supportsRunAllTests() {
    return true;
  }

  public function shouldEchoTestResults() {
    return false;
  }

  /**
   * @return null|array
   */
  public function getUnitConfigSection() {
    return $this->getConfigurationManager()->getConfigFromAnySource($this->getEngineConfigurationName());
  }

  /**
   * @param $name
   *
   * @return mixed|null
   */
  public function getUnitConfigValue($name) {
    $config = $this->getUnitConfigSection();
    return isset($config[$name]) ? $config[$name] : null;
  }

  public function run() {
    $this->projectRoot = $this->getWorkingCopy()->getProjectRoot();
    $include           = $this->getUnitConfigValue('include');
    $include_files      = $include !== null ? $this->getIncludedFiles($include) : array();

    foreach ($this->getPaths() as $path) {
      $path = Filesystem::resolvePath($path, $this->projectRoot);

      // Not yet support for directories
      // Users can call phpunit on the directory themselves
      if (is_dir($path)) {
        continue;
      }

      // Not sure if it would make sense to go further if it is not a JS file
      $extension = pathinfo($path, PATHINFO_EXTENSION);
      if (!in_array($extension, array('js', 'jsx', 'ts', 'tsx'))) {
        continue;
      }

      // do we have an include pattern? does it match the file?
      if (null !== $include && !in_array($path, $include_files, true)) {
        continue;
      }

      if (!Filesystem::pathExists($path)) {
        continue;
      }

      $this->affectedTests[$path] = basename($path);
    }

    if (empty($this->affectedTests)) {
      throw new ArcanistNoEffectException(pht('No tests to run.'));
    }

    $future = $this->buildTestFuture();
    list($err, $stdout, $stderr) = $future->resolve();

    // If we are running coverage the output includes a visual (non-JSON) representation
    // If that exists then exclude it before parsing the JSON.
    $json_start_index = strpos($stdout, '{"');
    $json_string      = substr($stdout, $json_start_index);

    try {
      $json_result = phutil_json_decode($json_string);
    } catch (PhutilJSONParserException $ex) {
      $cmd = $this->command;
      throw new CommandException(
        pht(
          "JSON command '%s' did not produce a valid JSON object on stdout: %s",
          $cmd,
          $stdout),
        $cmd,
        0,
        $stdout,
        $stderr
      );
    }
    $test_results = $this->parseTestResults($json_result);

    // getEnableCoverage() returns either true, false, or null
    // true and false means it was explicitly turned on or off.  null means use the default
    if ($this->getEnableCoverage() !== false) {
      $coverage = $this->readCoverage($json_result);

      foreach ($test_results as $test_result) {
        $test_result->setCoverage($coverage);
      }
    }

    return $test_results;
  }

  /**
   * @param String $include
   *
   * @return array|false
   */
  public function getIncludedFiles($include) {
    $dir = new RecursiveDirectoryIterator($this->projectRoot.$include);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, '%\.{js|ts|tsx|jsx}%');

    $file_list = array();
    foreach ($files as $file) {
      $file_list[] = $file->getRealPath();
    }

    return $file_list;
  }

  public function buildTestFuture() {
    $config = $this->getUnitConfigSection();
    $command = array_key_exists('bin', $config)
      ? "{$config['bin']} "
      : $this->getWorkingCopy()->getProjectRoot().'/node_modules/.bin/jest --json ';

    $command .= implode(' ', array_unique($this->affectedTests));

    // getEnableCoverage() returns either true, false, or null
    // true and false means it was explicitly turned on or off.  null means use the default
    if ($this->getEnableCoverage() !== false) {
      $command .= ' --coverage';
    }

    $this->command = $command;

    return new ExecFuture('%C', $command);
  }

  /**
   * @param Object $json_result
   *
   * @return array
   */
  public function parseTestResults($json_result) {
    $results = array();

    if ($json_result['numTotalTests'] === 0 && $json_result['numTotalTestSuites'] === 0) {
      throw new ArcanistNoEffectException(pht('No tests to run.'));
    }

    foreach ($json_result['testResults'] as $test_result) {
      $duration_in_seconds = ($test_result['endTime'] - $test_result['startTime']) / 1000;
      $status_result       = $test_result['status'] === 'passed' ?
        ArcanistUnitTestResult::RESULT_PASS :
        ArcanistUnitTestResult::RESULT_FAIL;

      $extra_data = array();
      foreach ($test_result['assertionResults'] as $assertion) {
        $extra_data[] = $assertion['status'] === 'passed'
          ? " [+] {$assertion['fullName']}"
          : " [!] {$assertion['fullName']}";
      }

      $result = new ArcanistUnitTestResult();
      $result->setName($test_result['name']);
      $result->setResult($status_result);
      $result->setDuration($duration_in_seconds);
      $result->setUserData($test_result['message']);
      $result->setExtraData($extra_data);
      $results[] = $result;
    }

    return $results;
  }

  /**
   * @param array $json_result
   *
   * @return array
   */
  private function readCoverage($json_result) {
    if (empty($json_result) || !isset($json_result['coverageMap'])) {
      return array();
    }

    $reports = array();
    foreach ($json_result['coverageMap'] as $file => $coverage) {
      $should_skip = strpos($file, '__fixtures__') !== false
        || strpos($file, '__mocks__') !== false
        || strpos($file, 'spec') !== false;

      if ($should_skip) {
        continue;
      }

      $line_count = count(file($file));
      $file = str_replace($this->projectRoot.DIRECTORY_SEPARATOR, '', $file);
      $reports[$file] = str_repeat('U', $line_count); // not covered by default

      foreach ($coverage['statementMap'] as $chunk) {
        for ($i = $chunk['start']['line']; $i < $chunk['end']['line']; $i++) {
          $reports[$file][$i] = 'C';
        }
      }
    }

    return $reports;
  }

  /**
   * @param string $path
   *
   * @return array
   */
  public function getSearchLocationsForTests($path) {
    $test_dir_names = $this->getUnitConfigValue('test.dirs');
    $test_dir_names = !empty($test_dir_names) ? $test_dir_names : array('tests', 'Tests');

    // including 5 levels of sub-dirs
    foreach ($test_dir_names as $dir) {
      $test_dir_names[] = $dir.'/**/';
      $test_dir_names[] = $dir.'/**/**/';
      $test_dir_names[] = $dir.'/**/**/**/';
      $test_dir_names[] = $dir.'/**/**/**/**/';
    }

    return $test_dir_names;
  }
}
