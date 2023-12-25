<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use App\Helpers\{CacheFetcherHelper, DOMHelper};

class Home extends AbstractController
{
    /**
     * @var DOMHelper $domHelper
     */
    private $domHelper;

    /**
     * @var CacheFetcherHelper $cacheFetcherHelper
     */
    private $cacheFetcherHelper;

    public function __construct(DOMHelper $domHelper, CacheFetcherHelper $cacheFetcherHelper)
    {
        $this->domHelper = $domHelper;
        $this->cacheFetcherHelper = $cacheFetcherHelper;
    }

    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return Response
     */
    public function index()
    {
        $links = [];
        try {
            $commitStripXml = $this->cacheFetcherHelper->fetchWithCache($this->getParameter('commitstripRssFeedUrl'), 'commitstrip_cache.xml', true, false);
            $possibleFormats = ['jpg', 'JPG', 'GIF', 'gif', 'PNG', '.png'];
            foreach ($commitStripXml->channel->item as $item) {
                $hasFormat = array_reduce($possibleFormats, function ($acc, $format) use ($item) {
                    return $acc + substr_count((string) $item->children('content', true), $format);
                }, 0);

                if (!$hasFormat) {
                    continue;
                }

                $links[] = (string) $item->link[0];
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $images = [];
        try {
            $apiKey = $this->getParameter('newsAPIKey');
            $url = $this->getParameter('newsAPIUrl') . '?country=us&apiKey=' . urlencode($apiKey);
            $newsAPIData = $this->cacheFetcherHelper->fetchWithCache($url, 'newsapi_cache.json', false);
        } catch (\Exception $e) {
            throw $e;
        }

        $newsAPIData = json_decode($newsAPIData);

        foreach ($newsAPIData->articles as $article) {
            if (
                empty($article->urlToImage) ||
                !$article ||
                in_array($article->url, $links)
            ) {
                continue;
            }

            $images[] = $article->url;
        }

        $images = array_merge($images, $links);

        $data = [];
        foreach ($images as $image) {
            try {
                $query = strstr($image, 'commitstrip.com') ? '//img[contains(@class,"size-full")]/@src' : '//img/@src';
                $data[] = $this->domHelper->getFirstElementValueByQuery($image, $query);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $this->render('default/index.html.twig', ['images' => $data]);
    }
}
