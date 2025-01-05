<?php
// modification.php
class Custom_UpdateServer extends Wpup_UpdateServer {
    
    protected function findPackage($slug) {
        // Define the suffix to be removed
        $removeSuffix = '-example';

        // Sanitize the slug to ensure it's safe for file handling
        $safeSlug = preg_replace('@[^a-z0-9\-_\.,+!]@i', '', $slug);

        // Check if the slug ends with the suffix and remove it
        if (substr($safeSlug, -strlen($removeSuffix)) === $removeSuffix) {
            $safeSlug = substr($safeSlug, 0, -strlen($removeSuffix));
        }


        // List of slugs that should never be served
        $neverServe = array('blacklisted-plugin', 'restricted-plugin', 'test-plugin');
        if (in_array($safeSlug, $neverServe)) {
            return null; // Never serve these slugs
        }

        // Check known routes for specific slug-to-filename mappings
        $knownRoutes = array(
            'burt' => 'someplugin.zip',
            'alternate-slug' => 'actual-plugin-name.zip',
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
