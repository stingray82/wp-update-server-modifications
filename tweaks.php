<?php
// modification.php
class custom_UpdateServer extends Wpup_UpdateServer {

    // Constructor to override default directories
    public function __construct($serverUrl = null, $serverDirectory = null) {
        if ($serverDirectory === null) {
            // Set custom directory outside the HTTP root.
            $serverDirectory = '/home/user/plugin-updates';
        }
        parent::__construct($serverUrl, $serverDirectory);

        // Override the package, logs, and asset directories with custom paths.
        $this->packageDirectory = $serverDirectory . '/custom-packages';
        $this->logDirectory = $serverDirectory . '/custom-logs';

        $this->bannerDirectory = $serverDirectory . '/custom-assets/banners';
        $this->assetDirectories = array(
            'banners' => $this->bannerDirectory,
            'icons'   => $serverDirectory . '/custom-assets/icons',
        );

        // Optionally, you can set a custom cache directory as well.
        $this->cache = new Wpup_FileCache($serverDirectory . '/custom-cache');
    }
    

    protected function findPackage($slug) {
        // Define an array of suffixes to be removed
        /*
         * To add new suffixes:
         * 1. Add a new string to the $suffixes array, following the same format.
         * 2. Example: '-example', '-v2', '-free'
         * 3. Each suffix should be a unique string that identifies a pattern to be removed.
         */
        $suffixes = [
            '-rupninja'
        ];

        // Sanitize the slug to ensure it's safe for file handling
        $safeSlug = preg_replace('@[^a-z0-9\-_\.,+!]@i', '', $slug);

        // Loop through each suffix and check if the slug ends with it
        foreach ($suffixes as $suffix) {
            if (substr($safeSlug, -strlen($suffix)) === $suffix) {
                // Remove the suffix from the slug
                $safeSlug = substr($safeSlug, 0, -strlen($suffix));
                break; // Stop once a match is found
            }
        }       


        // List of slugs that should never be served
        $neverServe = array('blacklisted-plugin', 'restricted-plugin', 'test-plugin');
        if (in_array($safeSlug, $neverServe)) {
            return null; // Never serve these slugs
        }

        // Check known routes for specific slug-to-filename mappings
        $knownRoutes = array(
            'burt' => 'frames-plugin.zip',
            //'wpcodebox' => 'wpcodebox2.zip',
            'custom-slug'    => 'different-plugin-name.zip',
            'another-slug'   => 'specific-package.zip',
        );

        if (array_key_exists($safeSlug, $knownRoutes)) {
            $filename = $this->packageDirectory . '/' . $knownRoutes[$safeSlug];
            if (is_file($filename) && is_readable($filename)) {
                return ($this->packageFileLoader)($filename, $slug, $this->cache);
            }
        }

        // List of exceptions that should not be modified
        $exceptions = array('specialplugin', 'customplugin');
        if (in_array($safeSlug, $exceptions)) {
            $filename = $this->packageDirectory . '/' . $safeSlug . '.zip';
            if (is_file($filename) && is_readable($filename)) {
                return ($this->packageFileLoader)($filename, $slug, $this->cache);
            }
            return null;
        }

        // Define modifiers to try different file naming conventions
        $modifiers = array('', '-plugin', '-pro', '-premium', '-tweaks-and-updates');
        
        // Try with the original slug and modifiers
        foreach ($modifiers as $modifier) {
            $filename = $this->packageDirectory . '/' . $safeSlug . $modifier . '.zip';
            if (is_file($filename) && is_readable($filename)) {
                return ($this->packageFileLoader)($filename, $slug, $this->cache);
            }
        }

        // Try stripping suffixes from the slug
        foreach ($modifiers as $modifier) {
            $suffixWithoutHyphen = str_replace('-', '', $modifier);
            if (substr($safeSlug, -strlen($modifier)) === $modifier || substr($safeSlug, -strlen($suffixWithoutHyphen)) === $suffixWithoutHyphen) {
                $strippedSlug = preg_replace('/(' . preg_quote($modifier, '/') . '|' . preg_quote($suffixWithoutHyphen, '/') . ')$/', '', $safeSlug);
                $filename = $this->packageDirectory . '/' . $strippedSlug . '.zip';
                if (is_file($filename) && is_readable($filename)) {
                    return ($this->packageFileLoader)($filename, $slug, $this->cache);
                }
            }
        }

        // If no files are found, return null
        return null;
    }
}
