<?php

namespace CategoryBanner\Controller;

use CategoryBanner\CategoryBanner;
use CategoryBanner\Form\BannerForm;
use CategoryBanner\Model\Banner;
use CategoryBanner\Model\BannerQuery;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Template\ParserContext;
use Thelia\Files\FileManager;
use Thelia\Model\Base\LangQuery;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Tools\URL;
use Twig\Parser;


#[Route('/admin/module/CategoryBanner/banner', name: 'categorybanner_banner_')]
class BannerConfigController extends BaseAdminController
{
    #[Route('/create', name: 'create', methods: 'POST')]
    public function createBanner(ParserContext $parserContext, FileManager $fileManager)
    {
        $form = $this->createForm(BannerForm::getName());

        try {
            $bannerForm = $this->validateForm($form);

            $lang = LangQuery::create()->filterByByDefault(1)->findOne();
            $newBanner = new Banner();
            $newBanner
                ->setLocale($lang?->getLocale() ?? 'en_US')
                ->setTitle($bannerForm->get('title')?->getData())
                ->setDescription($bannerForm->get('description')?->getData())
                ->setUrl($bannerForm->get('url')?->getData())
                ->setButtonLabel($bannerForm->get('button_label')?->getData())
                ->save();

            /** @var UploadedFile $file */
            $file = $bannerForm->get('image_file')?->getData();
            if ($file) {
                $fileName = $fileManager->sanitizeFileName($newBanner->getId().$file->getClientOriginalName());
                $newBanner
                    ->setImage($fileName)
                    ->save();
                $this->saveImage($file, $fileName);
            }

            return $this->generateSuccessRedirect($form);

        } catch (\Exception $e) {
            $parserContext
                ->addForm($form)
                ->setGeneralError($e->getMessage());
            return $this->generateErrorRedirect($form);
        }
    }

    #[Route('/delete', name: 'delete', methods: 'POST')]
    public function deleteBanner(Request $request)
    {
        $bannerId = $request->get('banner_id');

        BannerQuery::create()->findPk($bannerId)?->delete();

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/CategoryBanner'));
    }

    #[Route('/{id}', name: 'get_banner_page', methods: 'GET')]
    public function renderBannerEditPage(Request $request, $id)
    {
        if (!$langId = $request->get('edit_language_id')){
            $langId = LangQuery::create()->filterByByDefault(1)->findOne()?->getId();
        }

        return $this->render('edit-banner', [
            'bannerId' => $id,
            'edit_language_id' => $langId,
        ], 200);
    }

    #[Route('/{id}', name: 'update_banner', methods: 'POST')]
    public function updateBanner(Request $request, $id, FileManager $fileManager, ParserContext $parserContext)
    {
        $form = $this->createForm(BannerForm::getName());

        try {
            $bannerForm = $this->validateForm($form);

            $lang = $request->getSession()->get("thelia.admin.edition.lang");
            $banner = BannerQuery::create()->findPk($id);
            $banner
                ->setLocale($lang?->getLocale() ?? 'en_US')
                ->setTitle($bannerForm->get('title')?->getData())
                ->setDescription($bannerForm->get('description')?->getData())
                ->setUrl($bannerForm->get('url')?->getData())
                ->setButtonLabel($bannerForm->get('button_label')?->getData())
                ->save();

            /** @var UploadedFile $file */
            $file = $bannerForm->get('image_file')?->getData();
            if ($file) {
                $fileName = $fileManager->sanitizeFileName($banner->getId().$file->getClientOriginalName());
                $banner
                    ->setImage($fileName)
                    ->save();
                $this->saveImage($file, $fileName);
            }

            return $this->generateSuccessRedirect($form);

        } catch (\Exception $e) {
            $parserContext
                ->addForm($form)
                ->setGeneralError($e->getMessage());
            return $this->generateErrorRedirect($form);
        }
    }

    protected function saveImage(UploadedFile $file, $fileName)
    {
        $fs = new Filesystem();

        if (!$fs->exists(CategoryBanner::CATEGORY_BANNER_IMAGE_MEDIA_FOLDER)) {
            $fs->mkdir(CategoryBanner::CATEGORY_BANNER_IMAGE_MEDIA_FOLDER);
        }

        $fs->rename(
            $file->getPathname(),
            CategoryBanner::CATEGORY_BANNER_IMAGE_MEDIA_FOLDER.$fileName
        );
    }
}