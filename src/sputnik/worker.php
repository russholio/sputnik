#!/usr/bin/env php
<?php

namespace sputnik;

require_once __DIR__ . '/../../vendor/autoload.php';

use GearmanWorker;
use GearmanJob;
use sputnik\Model\Entity\JobDescription;

\E2EX\Converter::register(E_ALL);

function ensureJobIsAvailable(Model\Entity\JobDescription $job)
{
    echo $job . "\n";

    if (!file_exists('vendor/'. $job->repository)) {
        echo "Initiating composer install of package: $job->repository ($job->version)\n";
        var_dump("composer require $job->repository:$job->version");
        passthru("composer require $job->repository:$job->version", $result);

        var_dump($result);

        if ($result != 0 || !file_exists('vendor/'. $job->repository)) {
            throw new \Exception('Installing the required Job failed');
        }
    }
}

function runJob(Model\Entity\JobDescription $job)
{
    $command = "vendor/$job->repository/$job->runCommand";

    foreach ($job->arguments as $i) {
        $command .= ' ' . escapeshellarg($i);
    };

    $command = escapeshellcmd($command);

    var_dump($command);
    exec($command, $output, $result);

    echo (implode("\n", $output));
}

$worker = new GearmanWorker;
$worker->addServer('127.0.0.1');
$worker->addFunction('sputnik', function(GearmanJob $job) {
    $jd = new JobDescription(unserialize($job->workload()));

    ensureJobIsAvailable($jd);
    runJob($jd);
});

print "Waiting for job...\n";
while ($worker->work()) {
    if ($worker->returnCode() != GEARMAN_SUCCESS) {
        echo "return_code: " . $worker->returnCode() . "\n";
        break;
    }
}

