<?php


class Kohana2ValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        exec('grep -q \$kohana '.$sitePath.'/index.php', $out, $status);
        return $status === 0;
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if ($this->isActualFile($staticFilePath = $sitePath.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        if ($uri !== '/') {
            $this->forceTrailingSlash($sitePath, $uri);

            $dynamicCandidates = [
                $this->asPhpIndexFileInDirectory($sitePath, $uri),
                $this->asActualFile($sitePath, $uri),
                $this->asHtmlIndexFileInDirectory($sitePath, $uri),
            ];

            foreach ($dynamicCandidates as $candidate) {
                if ($this->isActualFile($candidate)) {
                    $_SERVER['SCRIPT_FILENAME'] = $candidate;
                    $_SERVER['SCRIPT_NAME'] = str_replace($sitePath, '', $candidate);
                    $_SERVER['DOCUMENT_ROOT'] = $sitePath;
                    return $candidate;
                }
            }
        }

        // it's the Kohana front controller
        $_GET['kohana_uri'] = $uri;
        return $sitePath.'/index.php';
    }

    /**
     * Concatenate the site path and URI as a single file name.
     *
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string
     */
    protected function asActualFile($sitePath, $uri)
    {
        return $sitePath.$uri;
    }

    /**
     * Format the site path and URI with a trailing "index.php".
     *
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string
     */
    protected function asPhpIndexFileInDirectory($sitePath, $uri)
    {
        return $sitePath.rtrim($uri, '/').'/index.php';
    }

    /**
     * Format the site path and URI with a trailing "index.html".
     *
     * @param  string  $sitePath
     * @param  string  $uri
     * @return string
     */
    protected function asHtmlIndexFileInDirectory($sitePath, $uri)
    {
        return $sitePath.rtrim($uri, '/').'/index.html';
    }

    /**
     * Determine if the path is a file and not a directory.
     *
     * @param  string  $path
     * @return bool
     */
    protected function isActualFile($path)
    {
        return !is_dir($path) && file_exists($path);
    }

    /**
     * Redirect to uri with trailing slash.
     *
     * @param  string $sitePath
     * @param  string $uri
     */
    protected function forceTrailingSlash($sitePath, $uri)
    {
        if (substr($uri, -1) != '/' && is_dir($sitePath . $uri)) {
            header('Location: ' . $uri . '/');
            die;
        }
    }
}
