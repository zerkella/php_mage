<?php
// Number of samples in the Checkout scenario - sorry, hardcoded
define('NUM_SAMPLES_IN_CHECKOUT', 13);

$configFile = __DIR__ . '/config.php';
$configFile = file_exists($configFile) ? $configFile : "$configFile.dist";
$config = require($configFile);

// Test required data
$required = array('host', 'path', 'scenario_users', 'scenario_loops');
foreach ($required as $key) {
    if (!isset($config[$key])) {
        throw new Exception("'{$key}' configuration option is not defined");
    }
}

// Validate JMeter command presence
$jMeterJarFile = getenv('jmeter_jar_file') ?: 'ApacheJMeter.jar';
$jMeterExecutable = 'java -jar ' . escapeshellarg($jMeterJarFile);
exec("$jMeterExecutable --version 2>&1", $jMeterOutput, $exitCode);
if ($exitCode) {
    echo implode(PHP_EOL, $jMeterOutput);
    exit($exitCode);
}

// Prepare report dir
$reportDir = __DIR__ . '/reports';
if (!is_dir($reportDir)) {
    $result = mkdir($reportDir, 0x666);
    if (!$result) {
        throw new Exception("Couldn't create directory for reports: {$reportDir}");
    }
}

foreach (glob($reportDir . '/*') as $filename) {
    unlink($filename);
}

// Prepare data to run scenarios
$scenarioFile = __DIR__ . '/scenario/checkout.jmx';
$reportFile = $reportDir . '/checkout.jtl';
$scenarioParams = array(
    'host' => $config['host'],
    'path' => $config['path'],
    'users' => $config['scenario_users'],
    'loops' => $config['scenario_loops'],
);

echo "\n";

// Warmup
$dryRunParams = array_merge($scenarioParams, array('users' => 1, 'loops' => 2));
$cmd = buildJMeterCmd($jMeterExecutable, $scenarioFile, $dryRunParams);
echo "---Running warmup---\n";
echo "{$cmd}\n";
passthru($cmd, $exitCode);
if ($exitCode) {
    echo "Failure\n";
    exit($exitCode);
}
echo "\n\n";

// Real scenario run
$cmd = buildJMeterCmd($jMeterExecutable, $scenarioFile, $scenarioParams, $reportFile);
echo "---Running scenario---\n";
echo "{$cmd}\n";
passthru($cmd, $exitCode);
if ($exitCode) {
    echo "Failure\n";
    exit($exitCode);
}
echo "\n\n";


// Calculate the time
$simpleXML = new SimpleXMLElement(file_get_contents($reportFile));

$failures = $simpleXML->xpath('//failureMessage');
foreach ($failures as $failure) {
    throw new Exception('Failure: ' . (string) $failure);
}

$nodes = $simpleXML->xpath('/testResults/httpSample');
$numSamples = count($nodes);
$numRuns = $scenarioParams['users'] * $scenarioParams['loops'];
$expectedSamples = $numRuns * NUM_SAMPLES_IN_CHECKOUT;
if ($numSamples != $expectedSamples) {
    throw new Exception("Expected {$expectedSamples}, however log contains only {$numSamples}");
}

$totalTime = 0;
foreach ($nodes as $node) {
    $attributes = $node->attributes();
    $totalTime += $attributes['t'];
}
$averageTime = $totalTime / $numRuns;
printf("\nAverage checkout time: %.2f seconds\n", $averageTime / 1000);

/**
 * @param string $jMeterExecutable
 * @param string $scenario
 * @param array $params
 * @param null|string $logFile
 * @return string
 */
function buildJMeterCmd($jMeterExecutable, $scenario, array $params = array(), $logFile = null) {
    $result = $jMeterExecutable . ' -n -t ' . escapeshellarg($scenario);
    if ($logFile) {
        $result .= ' -l ' . escapeshellarg($logFile);
    }
    foreach ($params as $key => $value) {
        $result .= " -J$key=$value";
    }
    return $result;
}
