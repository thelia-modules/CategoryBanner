<?php

namespace CategoryBanner\Controller;

use CategoryBanner\CategoryBanner;
use CategoryBanner\Form\BannerForm;
use CategoryBanner\Model\Banner;
use CategoryBanner\Model\BannerQuery;
use CategoryBanner\Service\BannerDataService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\File\FileManager;
use Thelia\Model\Base\LangQuery;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Tools\URL;
use Twig\Environment;


#[Route('/admin/module/CategoryBanner/banner', name: 'categorybanner_banner_')]
class BannerConfigController extends BaseAdminController
{
    public function __construct(private readonly Environment $twig)
    {
    }

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
    public function renderBannerEditPage(Request $request, $id, BannerDataService $bannerDataService)
    {
        if (!$langId = $request->get('edit_language_id')){
            $langId = LangQuery::create()->filterByByDefault(1)->findOne()?->getId();
        }

        $locale = LangQuery::create()->findPk($langId)?->getLocale() ?? 'en_US';

        return new Response(
            $this->twig->render('@CategoryBannerModule/backOffice/default-twig/CategoryBanner/edit-banner.html.twig', [
                'bannerId' => (int) $id,
                'edit_language_id' => (int) $langId,
                'banner' => $bannerDataService->getBanner((int) $id, $locale, 580),
            ])
        );
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