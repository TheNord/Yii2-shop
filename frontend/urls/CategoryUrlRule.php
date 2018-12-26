<?php

namespace frontend\urls;

use shop\entities\Shop\Category;
use shop\readModels\Shop\CategoryReadRepository;
use yii\base\InvalidParamException;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\web\UrlNormalizerRedirectException;
use yii\web\UrlRuleInterface;

/**
 * Класс для изменения адресов каталога в вид ЧПУ
 * наследуется от Object для возможности подставлять параметры:
 * [''prefix' => '....']
 */
class CategoryUrlRule extends Object implements UrlRuleInterface
{
    public $prefix = 'catalog';

    private $repository;

    public function __construct(CategoryReadRepository $repository, $config = [])
    {
        parent::__construct($config);
        $this->repository = $repository;
    }

    /** Парсим реквест */
    public function parseRequest($manager, $request)
    {
        // добавляем префикс и получаем текущий путь из реквеста (catalog/phone/...), в карманы записываем
        // (matches) все дополнитльные категории
        // если в адресе нет catalog, возвращаем false и UrlManager идет дальше
        if (preg_match('#^' . $this->prefix . '/(.*[a-z])$#is', $request->pathInfo, $matches)) {
            $path = $matches['1'];

            // ищем категорию по последнему слагу
            if (!$category = $this->repository->findBySlug($this->getPathSlug($path))) {
                return false;
            }

            // если категория нашлась но полученный путь отличается от сгенерированного, редеректим на правильный
            if ($path != $this->getCategoryPath($category)) {
                throw new UrlNormalizerRedirectException(['shop/catalog/category', 'id' => $category->id], 301);
            }

            // если категория нашлась возвращаем маршрут контроллера и передаем ид категории
            return ['shop/catalog/category', ['id' => $category->id]];
        }
        return false;
    }

    /** Генерируем адреса */
    public function createUrl($manager, $route, $params)
    {
        // прверяем маршрут, чтобы не обрабатывть чужие адреса
        if ($route == 'shop/catalog/category') {
            if (empty($params['id'])) {
                throw new InvalidParamException('Empty id.');
            }

            // ищем категорию по переданному ид
            if (!$category = $this->repository->find($params['id'])) {
                throw new InvalidParamException('Undefined id.');
            }

            // генерируем полный путь добавляя префикс
            $url = $this->prefix . '/' . $this->getCategoryPath($category);

            // удаляем идшник из параметров
            unset($params['id']);
            // если еще что то осталось генерируем квери стринг строку
            // чтобы сохранять параметры в гет запросах (сортировки и тд)
            if (!empty($params) && ($query = http_build_query($params)) !== '') {
                // подключаем к адресу после знака вопроса
                $url .= '?' . $query;
            }

            return $url;
        }
        return false;
    }

    /** Разбиваем слаги по частям через слэш */
    private function getPathSlug($path): string
    {
        $chunks = explode('/', $path);
        return end($chunks);
    }

    /** Генерируем путь из родительских категорий */
    private function getCategoryPath(Category $category): string
    {
        $chunks = ArrayHelper::getColumn($category->getParents()->andWhere(['>', 'depth', 0])->all(), 'slug');

        $chunks[] = $category->slug;

        return implode('/', $chunks);
    }
}