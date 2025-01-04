**wp-update-server-modifications**
==================================

**Please Note:  I deleted the old "fork" and made this a dedicated update as I
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
