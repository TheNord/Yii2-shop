<?php

namespace frontend\urls;

use shop\entities\Page;
use shop\readModels\PageReadRepository;
use yii\base\InvalidParamException;
use yii\base\Object;
use yii\caching\Cache;
use yii\helpers\ArrayHelper;
use yii\web\UrlNormalizerRedirectException;
use yii\web\UrlRuleInterface;

class PageUrlRule extends Object implements UrlRuleInterface
{
    private $repository;
    private $cache;

    public function __construct(PageReadRepository $repository, Cache $cache, $config = [])
    {
        parent::__construct($config);
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /** Парсим Url и обрабатываем его только тогда когда другие UrlManager'ы его не нашли
     *  Для открытия страниц по адресу site.ru/about
     */
    public function parseRequest($manager, $request)
    {
        $path = $request->pathInfo;

        $result = $this->cache->getOrSet(['page_route', 'path' => $path], function () use ($path) {
            // находим страницу по слагу
            if (!$page = $this->repository->findBySlug($this->getPathSlug($path))) {
                return ['id' => null, 'path' => null];
            }
            // возвращаем ид страницы и путь
            return ['id' => $page->id, 'path' => $this->getPagePath($page)];
        });

        if (empty($result['id'])) {
            return false;
        }

        // переадрессовываем если url был введен ошибочным или мы перенесли страницу: site.ru/asdasd/about/,
        // по последнему слагу найдет about и перекинет на него
        if ($path != $result['path']) {
            throw new UrlNormalizerRedirectException(['page/view', 'id' => $result['id']], 301);
        }

        return ['page/view', ['id' => $result['id']]];
    }

    public function createUrl($manager, $route, $params)
    {
        if ($route == 'page/view') {
            if (empty($params['id'])) {
                throw new InvalidParamException('Empty id.');
            }

            $id = $params['id'];

            $url = $this->cache->getOrSet(['page_route', 'id' => $id], function () use ($id) {
                if (!$page = $this->repository->find($id)) {
                    return null;
                }
                return $this->getPagePath($page);
            });

            if (!$url) {
                throw new InvalidParamException('Undefined id.');
            }

            unset($params['id']);
            
            if (!empty($params) && ($query = http_build_query($params)) !== '') {
                $url .= '?' . $query;
            }

            return $url;
        }
        return false;
    }

    private function getPathSlug($path): string
    {
        $chunks = explode('/', $path);
        return end($chunks);
    }

    private function getPagePath(Page $page): string
    {
        $chunks = ArrayHelper::getColumn($page->getParents()->andWhere(['>', 'depth', 0])->all(), 'slug');
        $chunks[] = $page->slug;
        return implode('/', $chunks);
    }
}