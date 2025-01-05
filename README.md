**wp-update-server-modifications**
==================================

**Please Note: I deleted the old "fork" and made this a dedicated update as I
think it was confusing people about what it actually does & how to apply it and
these files and instructions give good building blocks**

 

A modified class for WP-Update-Server to handle additional modifications to the
find package routine other than the modifications contained within this folder
the rest is entirely "default" version of
https://github.com/YahnisElsts/wp-update-server

**Installing these modifications:**

Install https://github.com/YahnisElsts/wp-update-server

 

Add the following two lines to index.php in the main folder

`require __DIR__ . '/tweaks.php';`

`$server = new Custom_UpdateServer();`

Add tweaks.php to the root directory with the index file

Your done, I have included a version of the updateServer.php this was originally
written for to make tracking changes easier when I make modifications

 

**What are the modifications?**
-------------------------------

This documentation provides an overview of the custom modifications made to the
`Wpup_UpdateServer` class in `tweaks.php`, as well as the new custom class
`Custom_UpdateServer`. These changes enhance the functionality of the update
server by introducing additional checks, handling specific edge cases, and
providing more flexibility in serving plugin or theme update packages.

### **Overview of Changes**

1.  **Never Serve List**

2.  **Exceptions List**

3.  **Modifiers for Slug Variations**

4.  **Suffix Stripping Logic**

5.  **Known Routes Handling**

### **Custom Class:** `Custom_UpdateServer`

The custom class `Custom_UpdateServer` extends the original `Wpup_UpdateServer`
class and overrides the `findPackage` method to implement additional logic for
finding and serving update packages based on specific rules.

#### **Method:** `findPackage($slug)`

This method attempts to locate the correct update package for a given plugin or
theme slug, applying custom rules to improve the matching process.

### **Detailed Functionality**

#### 1. **Never Serve List**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$neverServe = array('blacklisted-plugin', 'restricted-plugin', 'test-plugin');
if (in_array($safeSlug, $neverServe)) {
    return null; // Never serve these slugs
}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

-   This list contains slugs for plugins or themes that should **never be
    served**, regardless of whether they exist in the package directory.

-   Example:

    -   If a request is made for `blacklisted-plugin`, the server will return
        `null`, ensuring the package is never served.

#### 2. **Exceptions List**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$exceptions = array('wpcodebox', 'specialplugin', 'customplugin');
if (in_array($safeSlug, $exceptions)) {
    $filename = $this->packageDirectory . '/' . $safeSlug . '.zip';
    if (is_file($filename) && is_readable($filename)) {
        return call_user_func($this->packageFileLoader, $filename, $slug, $this->cache);
    }
    return null;
}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

-   The exceptions list contains slugs that should be served without applying
    any modifiers or suffix stripping.

-   Example:

    -   If the slug `wpcodebox` is requested, it will directly attempt to serve
        `wpcodebox.zip` without any further modification.

#### 3. **Modifiers for Slug Variations**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$modifiers = array('', '-plugin', '-pro', '-premium', '-tweaks-and-updates');
foreach ($modifiers as $modifier) {
    $filename = $this->packageDirectory . '/' . $safeSlug . $modifier . '.zip';
    if (is_file($filename) && is_readable($filename)) {
        return call_user_func($this->packageFileLoader, $filename, $slug, $this->cache);
    }
}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

-   The modifiers list defines possible suffixes that may be appended to the
    slug when searching for the package.

-   Example:

    -   If the slug `exampleplugin` is requested, the method will check for:

        -   `exampleplugin.zip`

        -   `exampleplugin-plugin.zip`

        -   `exampleplugin-pro.zip`

        -   `exampleplugin-premium.zip`

        -   `exampleplugin-tweaks-and-updates.zip`

#### 4. **Suffix Stripping Logic**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
foreach ($modifiers as $modifier) {
    $suffixWithoutHyphen = str_replace('-', '', $modifier);
    if (substr($safeSlug, -strlen($modifier)) === $modifier || substr($safeSlug, -strlen($suffixWithoutHyphen)) === $suffixWithoutHyphen) {
        $strippedSlug = preg_replace('/(' . preg_quote($modifier, '/') . '|' . preg_quote($suffixWithoutHyphen, '/') . ')$/', '', $safeSlug);
        $filename = $this->packageDirectory . '/' . $strippedSlug . '.zip';
        if (is_file($filename) && is_readable($filename)) {
            return call_user_func($this->packageFileLoader, $filename, $slug, $this->cache);
        }
    }
}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

-   This logic attempts to strip common suffixes from the slug (both hyphenated
    and non-hyphenated versions) and checks if a package exists without the
    suffix.

-   Example:

    -   If the slug `exampleplugin-pro` is requested, it will strip `-pro` and
        check for `exampleplugin.zip`.

#### 5. **Known Routes Handling**

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$knownRoutes = array(
    'burt' => 'someplugin.zip',
    'alternate-slug' => 'actual-plugin-name.zip',
    'custom-slug'    => 'different-plugin-name.zip',
    'another-slug'   => 'specific-package.zip',
);
if (array_key_exists($safeSlug, $knownRoutes)) {
    $filename = $this->packageDirectory . '/' . $knownRoutes[$safeSlug];
    if (is_file($filename) && is_readable($filename)) {
        return call_user_func($this->packageFileLoader, $filename, $slug, $this->cache);
    }
}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

-   The known routes list maps specific slugs to different filenames, ensuring
    that packages can be served even if the slug doesn’t directly match the
    filename.

-   Example:

    -   If a request is made for the slug `burt`, the method will serve
        `fluentformpro.zip`.

### **Usage Example**

1.  **Requesting a Standard Package**

    -   **Slug**: `exampleplugin`

    -   The method will check for `exampleplugin.zip` and return it if found.

2.  **Requesting a Package with a Modifier**

    -   **Slug**: `exampleplugin-pro`

    -   The method will strip the `-pro` suffix and check for
        `exampleplugin.zip`.

3.  **Requesting a Known Route**

    -   **Slug**: `burt`

    -   Since `burt` is in the known routes list, it will directly serve
        `someplugin.zip`.

4.  **Requesting a Blacklisted Package**

    -   **Slug**: `blacklisted-plugin`

    -   Since `blacklisted-plugin` is in the never serve list, the method will
        immediately return `null`, ensuring the package is not served.

### **Summary**

These custom modifications provide greater flexibility and control over how
update packages are served. By introducing the never serve list, exceptions,
modifiers, suffix stripping, and known routes, the update server can handle a
wide range of scenarios and ensure only the correct packages are delivered.

 

 

 

Slug Mapping:
-------------

This allows you to remotely update slugs when there are clashes or when two
people use the same textdomain, this returns the JSON required for the
modifications below

 

Introduction
------------

This documentation explains the process of dynamically fetching a custom slug
mapping for the WordPress Plugin Update Checker. The purpose of this enhancement
is to resolve potential incompatibility issues by allowing the plugin to query
the update server with an alternative slug when a mapping exists. If no mapping
is found, it defaults to using the original slug.

How the Code Works
------------------

### Step 1: Including the Plugin Update Checker

The following line includes the `plugin-update-checker.php` file and uses its
namespace:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
require 'path/to/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This allows the use of the `PucFactory` class to create an update checker
instance.

### Step 2: Fetching the Slug Mapping from the Server

A function `get_slug_mapping_from_url` is defined to fetch the slug mapping from
a remote server. The server is expected to return a JSON object containing the
mappings.

#### Function Definition:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function get_slug_mapping_from_url($custom_server_url) {
    $slug_mapping_url = rtrim($custom_server_url, '/') . '/slug-mapping.php';
    $response = wp_safe_remote_get($slug_mapping_url);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return array(); // Fallback to an empty array if the request fails
    }

    $body = wp_remote_retrieve_body($response);
    $mapping = json_decode($body, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($mapping)) {
        return $mapping;
    }

    return array(); // Fallback to an empty array if JSON decoding fails
}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

#### Explanation:

1.  **URL Construction**: The function constructs the slug mapping URL by
    appending `/slug-mapping.php` to the custom server URL.

2.  **Fetching Data**: It uses `wp_safe_remote_get` to fetch the mapping from
    the URL.

3.  **Error Handling**: If the request fails or the response is not valid, it
    returns an empty array.

4.  **JSON Decoding**: The response body is decoded into an associative array.
    If decoding fails, it also returns an empty array.

### Step 3: Defining the Custom Server URL and Fetching the Mapping

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$custom_server_url = 'https://custom-update-server.com/access/';
$slug_mapping = get_slug_mapping_from_url($custom_server_url);
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Here, the custom server URL is defined, and the `get_slug_mapping_from_url`
function is called to fetch the slug mapping.

### Step 4: Checking for a Mapped Slug

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$original_slug = 'plugin-directory-name';
$metadata_url = 'https://custom-update-server.com/wp-update-server/?action=get_metadata&slug=' . $original_slug;

if (isset($slug_mapping[$original_slug])) {
    $mapped_slug = $slug_mapping[$original_slug];
    $metadata_url = 'https://custom-update-server.com/wp-update-server/?action=get_metadata&slug=' . $mapped_slug;
}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

1.  **Original Slug**: The `$original_slug` is defined as the plugin directory
    name.

2.  **Default Metadata URL**: The initial `metadata_url` is constructed using
    the original slug.

3.  **Slug Mapping Check**: If a mapping exists for the original slug, the
    `metadata_url` is updated to use the mapped slug.

### Step 5: Building the Update Checker

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$MyUpdateChecker = PucFactory::buildUpdateChecker(
    $metadata_url,   // Metadata URL (uses mapped slug if available)
    __FILE__,        // Full path to the main plugin file
    $original_slug   // Plugin slug (remains the same for local use)
);
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The `PucFactory::buildUpdateChecker` method is called with the following
parameters:

-   **Metadata URL**: Uses the mapped slug if available, otherwise defaults to
    the original slug.

-   **Plugin File Path**: `__FILE__` is passed to indicate the main plugin file.

-   **Plugin Slug**: The original slug is used for local operations and
    compatibility with WordPress.

How to Set Up the Slug Mapping Server
-------------------------------------

1.  **Create a PHP file (**`slug-mapping.php`**) on your server**:

    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    <?php
    header('Content-Type: application/json');

    $slug_mapping = array(
        "plugin-textdomain-xxx" => "plugin-server-slug-yyy",
        "theme-folder-aaa"      => "theme-server-slug-bbb"
    );

    echo json_encode($slug_mapping, JSON_PRETTY_PRINT);
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

2.  **Ensure the file is accessible via URL**: For example,
    `https://custom-update-server.com/access/slug-mapping.php`.

3.  **Update the** `$custom_server_url` **in the plugin code** to point to your
    server.

Summary
-------

This enhancement dynamically resolves slug compatibility issues by querying a
custom server for a slug mapping. The approach ensures:

-   Centralized control over slug mappings.

-   Real-time updates without requiring manual changes to plugin code.

-   Compatibility with third-party update servers that expect different slugs.

If no mapping is found, the plugin defaults to using the original slug, ensuring
backward compatibility.

Benefits
--------

-   **Dynamic Updates**: Any changes to the slug mapping on the server are
    instantly reflected without updating the plugin.

-   **Reduced Maintenance**: Centralized slug mapping simplifies updates and
    maintenance.

-   **Improved Compatibility**: Ensures compatibility with custom update servers
    that require specific slugs.

Notes
-----

-   Ensure that the custom server URL and `slug-mapping.php` file are correctly
    configured and accessible.

-   The slug mapping file must return a valid JSON object for the plugin to work
    correctly.

For further questions or issues, please contact the developer or refer to the
WordPress Plugin Update Checker documentation.
