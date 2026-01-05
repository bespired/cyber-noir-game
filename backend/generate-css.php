<?php

/**
 * Cyber Noir CSS Extractor & Refactorer
 * 1. Scans the frontend for class attributes.
 * 2. Collects CSS rules from style.css and Vue files.
 * 3. Maps unique tag+classes to incremental names.
 * 4. REPLACES class strings in .vue files.
 * 5. REPLACES style.css with a consolidated version.
 */

$sourceDir = __DIR__ . '/frontend/src';
$stylePath = $sourceDir . '/style.css';
$outputCss = $stylePath; // We will overwrite style.css

$cssRules = [];
$elementClasses = []; // Stores [tag => [classList => incrementalName]]
$increments = []; // Stores [tagPrefix => count]

// 1. Collect all CSS rules
echo "Collecting CSS rules...\n";
$cssFiles = glob_recursive($sourceDir, '*.css');
$vueFiles = glob_recursive($sourceDir, '*.vue');

foreach ($cssFiles as $file) {
    extractCssRules(file_get_contents($file), $cssRules);
}

foreach ($vueFiles as $file) {
    $content = file_get_contents($file);
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $content, $matches)) {
        foreach ($matches[1] as $styleContent) {
            extractCssRules($styleContent, $cssRules);
        }
    }
}

// 2. Scan templates for class usage and generate mappings
echo "Generating mappings...\n";
foreach ($vueFiles as $file) {
    $content = file_get_contents($file);
    if (preg_match_all('/<([a-z0-9-]+)[^>]*\sclass=["\']([^"\']+)["\']/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tag = strtolower($match[1]);
            $classList = trim($match[2]);
            if (empty($classList))
                continue;

            // Normalize class list
            $classes = array_filter(explode(' ', $classList));
            sort($classes);
            $normalizedClassList = implode(' ', $classes);

            if (!isset($elementClasses[$tag][$normalizedClassList])) {
                switch ($tag) {
                    case 'div':
                        $prefix = 'd';
                        break;
                    case 'button':
                        $prefix = 'b';
                        break;
                    case 'span':
                        $prefix = 's';
                        break;
                    case 'p':
                        $prefix = 'p';
                        break;
                    case 'input':
                        $prefix = 'i';
                        break;
                    case 'label':
                        $prefix = 'l';
                        break;
                    default:
                        $prefix = substr($tag, 0, 1);
                        break;
                }
                if (!isset($increments[$prefix])) {
                    $increments[$prefix] = 1;
                }
                $incrementalName = sprintf("%s-%04d", $prefix, $increments[$prefix]++);
                $elementClasses[$tag][$normalizedClassList] = $incrementalName;
            }
        }
    }
}

// 3. Replace classes in .vue files
echo "Replacing classes in .vue files...\n";
foreach ($vueFiles as $file) {
    $content = file_get_contents($file);
    $newContent = preg_replace_callback('/(<([a-z0-9-]+)[^>]*\sclass=["\'])([^"\']+)((["\']))/i', function ($m) use ($elementClasses) {
        $tag = strtolower($m[2]);
        $classList = trim($m[3]);
        if (empty($classList))
            return $m[0];

        $classes = array_filter(explode(' ', $classList));
        sort($classes);
        $normalizedClassList = implode(' ', $classes);

        if (isset($elementClasses[$tag][$normalizedClassList])) {
            return $m[1] . $elementClasses[$tag][$normalizedClassList] . $m[4];
        }
        return $m[0];
    }, $content);

    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "Updated $file\n";
    }
}

// 4. Generate new style.css
echo "Generating new $outputCss...\n";
if (file_exists($stylePath)) {
    $oldStyle = file_get_contents($stylePath);
    $lines = explode("\n", $oldStyle);
    $headerLines = array_slice($lines, 0, 54);
    $header = implode("\n", $headerLines) . "\n\n";
} else {
    $header = "/* Cyber Noir Base Styles */\n\n";
}

$output = $header;
$output .= "/* Cyber Noir Incremental Classes */\n\n";

foreach ($elementClasses as $tag => $mappings) {
    foreach ($mappings as $classList => $incrementalName) {
        $classes = explode(' ', $classList);
        $combinedStyles = "";
        foreach ($classes as $class) {
            $lookup = ltrim($class, '.');
            if (isset($cssRules[$lookup])) {
                $combinedStyles .= "    /* From .$lookup */\n" . $cssRules[$lookup] . "\n";
            }
        }

        if (!empty($combinedStyles)) {
            $output .= ".$incrementalName {\n" . $combinedStyles . "}\n\n";
        }
    }
}

file_put_contents($outputCss, $output);
echo "Done! Full refactor complete.\n";

/**
 * Functions
 */
function glob_recursive($base, $pattern, $flags = 0)
{
    $files = glob("$base/$pattern", $flags) ?: [];
    foreach (glob("$base/*", GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, glob_recursive($dir, $pattern, $flags));
    }
    return $files;
}

function extractCssRules($content, &$rules)
{
    $content = preg_replace('/\/\*.*?\*\//s', '', $content);
    if (preg_match_all('/\.([a-zA-Z0-9_-]+)\s*\{([^}]*)\}/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $className = $match[1];
            $style = trim($match[2]);
            if (isset($rules[$className])) {
                $rules[$className] .= "\n" . $style;
            } else {
                $rules[$className] = $style;
            }
        }
    }
}
