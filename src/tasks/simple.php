<?php
/**
 * Contains overwrites of Deployer core tasks to handle deployment with simple recipe (--> no release directories,...)
 * See README.MD for more information
 */

namespace Deployer;

use function Deployer\Support\str_contains;
use Deployer\Type\Csv;

/**
 * Overwrite deploy:prepare to not create release and other directories
 */
task('deploy:prepare', function () {
    // Check if shell is POSIX-compliant
    $result = run('echo $0');

    if (!str_contains($result, 'bash') && !str_contains($result, 'sh')) {
        throw new \RuntimeException(
            'Shell on your server is not POSIX-compliant. Please change to sh, bash or similar.'
        );
    }

    run('if [ ! -d {{deploy_path}} ]; then mkdir -p {{deploy_path}}; fi');

        // Create metadata .dep dir.
        run("cd {{deploy_path}} && if [ ! -d .dep ]; then mkdir .dep; fi");
})->desc('Preparing host for deploy');

/**
 * Overwrite releases_list to ignore contents release directory
 */
set('releases_list', function () {
    cd('{{deploy_path}}');

    $releases = []; // Releases list.

    // Collect releases based on .dep/releases info.
    // Other will be ignored.

    if (test('[ -f .dep/releases ]')) {
        $keepReleases = get('keep_releases');
        if ($keepReleases === -1) {
            $csv = run('cat .dep/releases');
        } else {
            // Instead of `tail -n` call here can be `cat` call,
            // but on hosts with a lot of deploys (more 1k) it
            // will output a really big list of previous releases.
            // It spoils appearance of output log, to make it pretty,
            // we limit it to `n*2 + 5` lines from end of file (15 lines).
            // Always read as many lines as there are release directories.
            $csv = run("tail -n " . max(count($releases), ($keepReleases * 2 + 5)) . " .dep/releases");
        }

        $metainfo = Csv::parse($csv);

        for ($i = count($metainfo) - 1; $i >= 0; --$i) {
            if (is_array($metainfo[$i]) && count($metainfo[$i]) >= 2) {
                list(, $release) = $metainfo[$i];
                $releases[] = $release;
            }
        }
    }

    return $releases;
});

/**
 * Overwrite deploy:release to not manage release directory
 */
task('deploy:release', function () {
    cd('{{deploy_path}}');

    // We need to get releases_list at same point as release_name,
    // as standard release_name's implementation depends on it and,
    // if user overrides it, we need to get releases_list manually.
    $releasesList = get('releases_list');
    $releaseName = get('release_name');

    // Metainfo.
    $date = run('date +"%Y%m%d%H%M%S"');

    // Save metainfo about release
    run("echo '$date,{{release_name}}' >> .dep/releases");

    // Add to releases list
    array_unshift($releasesList, $releaseName);
    set('releases_list', $releasesList);
})->desc('Prepare release. Clean up unfinished releases and prepare next release');

/**
 * Overwrite cleanup task
 * @todo maybe add things to cleanup
 */
task('cleanup', function () {
})->desc('Cleaning up old releases');
