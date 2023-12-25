<?php

namespace App\Helpers;

class CacheFetcherHelper
{
    public function fetchWithCache($url, $cacheFileName, $isXmlFetch = false, $decode = true, $cacheDuration = 3600)
    {
        $cachedData = null;
        if (file_exists($cacheFileName) && time() - filemtime($cacheFileName) < $cacheDuration) {
            $cachedData = file_get_contents($cacheFileName);
            if ($isXmlFetch) {
                $cachedData = $this->parsXml($cachedData);
            }

            $cachedData = $decode ? json_decode($cachedData) : $cachedData;
        } else if ($isXmlFetch) {
            try {
                $content = file_get_contents($url);
                file_put_contents($cacheFileName, $content);
                $cachedData = $this->parsXml($content);
            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: testSys/1.0',
            ]);
            $data = curl_exec($ch);

            if ($data !== false) {
                file_put_contents($cacheFileName, json_encode($data));
            }

            curl_close($ch);
            $cachedData = $data;
        }

        return $cachedData;
    }

    private function parsXml($xml)
    {
        $xml = simplexml_load_string($xml);
        if (!$xml) {
            throw new \Exception('Error loading XML data');
        }

        return $xml;
    }
}
