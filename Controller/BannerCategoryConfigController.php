<?php

namespace CategoryBanner\Controller;

use CategoryBanner\Model\BannerCategory;
use CategoryBanner\Model\BannerCategoryQuery;
use Propel\Runtime\Map\TableMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;

#[Route('/admin/module/CategoryBanner/category', name: 'categorybanner_category_')]
class BannerCategoryConfigController extends BaseAdminController
{
    #[Route('/save', name: 'save', methods: 'POST')]
    public function addBannerCategory(Request $request)
    {
        $id = $request->get('id');
        $bannerId = $request->get('banner');
        $position = $request->get('position');
        $size = $request->get('size');
        $categoryId = $request->get('category');

        $responseData = [
            'success' => false,
            'message' => '',
        ];

        try {

        $bannerCategory = BannerCategoryQuery::create()->filterById($id)->findOne();
        if (null === $bannerCategory) {
            $bannerCategory = new BannerCategory();
        }

        $bannerCategory
            ->setBannerId($bannerId)
            ->setCategoryId($categoryId)
            ->setPosition($position)
            ->setSize($size)
            ->save();

        $responseData['success'] = true;
        $responseData['banner'] = $bannerCategory->toArray(TableMap::TYPE_STUDLYPHPNAME);

        } catch (\Exception $e) {
            $responseData['message'] = $e->getMessage();
        }

        return $this->jsonResponse(json_encode($responseData));
    }


    #[Route('/delete', name: 'delete', methods: 'POST')]
    public function deleteBannerCategory(Request $request)
    {
        $id = $request->get('id');

        $responseData = [
            'success' => false,
            'message' => '',
        ];

        try {
            $bannerCategory = BannerCategoryQuery::create()->filterById($id)->findOne();
            $bannerCategory?->delete();

            $responseData['success'] = true;
            $responseData['banner'] = $bannerCategory?->toArray(TableMap::TYPE_STUDLYPHPNAME);

        }catch (\Exception $e) {
            $responseData['message'] = $e->getMessage();
        }

        return $this->jsonResponse(json_encode($responseData));

    }

}