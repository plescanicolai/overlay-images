<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController
 * @package AppBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @Route("image", name="image")
     * @param Request $request
     * @return array|BinaryFileResponse
     */
    public function imageAction(Request $request)
    {
        $horizontalForm = $this->createFormBuilder()->add('horizontal', FileType::class)->getForm();
        $verticalForm = $this->createFormBuilder()->add('vertical', FileType::class)->getForm();
        $horizontalForm->handleRequest($request);
        $verticalForm->handleRequest($request);
        if ($horizontalForm->isSubmitted() && $horizontalForm->isValid()) {
            $data = $horizontalForm->getData();
            /** @var UploadedFile $imageAdd */
            $imageAdd = $data['horizontal'];

            return $this->imageManipulation($imageAdd);
        }
        if ($verticalForm->isSubmitted() && $verticalForm->isValid()) {
            $data = $verticalForm->getData();
            /** @var UploadedFile $imageAdd */
            $imageAdd = $data['vertical'];

            return $this->imageManipulation($imageAdd);
        }

        return $this->render('default/image.html.twig', ['horizontalForma' => $horizontalForm->createView(),
                'verticalForm' => $verticalForm->createView(),
                'horizontalForm' => $horizontalForm->createView(),
        ]);
    }

    private function resizeImage($image, $newPath, $name, $height = 0, $width = 0)
    {
        $size = getimagesize($image);
        $heightOrig = $size[1];
        $widthOrig = $size[0];

        $fileExtension = 'jpg';
        $jpegQuality = 75;
        $width = round($width);
        $height = round($height);

        $gdImageDest = imagecreatetruecolor($width, $height);
        $gdImageSrc = null;
        switch ($fileExtension) {
            case 'png':
                $gdImageSrc = imagecreatefrompng($image);
                imagealphablending($gdImageDest, false);
                imagesavealpha($gdImageDest, true);
                break;
            case 'jpeg':
            case 'jpg':
                $gdImageSrc = imagecreatefromjpeg($image);
                break;
            case 'gif':
                $gdImageSrc = imagecreatefromgif($image);
                break;
            default:
                break;
        }

        imagecopyresampled($gdImageDest, $gdImageSrc, 0, 0, 0, 0, $width, $height, $widthOrig, $heightOrig);

        $newFileName = $newPath.'/'.$name;

        switch ($fileExtension) {
            case 'png':
                imagepng($gdImageDest, $newFileName);
                break;
            case 'jpeg':
            case 'jpg':
                imagejpeg($gdImageDest, $newFileName, $jpegQuality);
                break;
            case 'gif':
                imagegif($gdImageDest, $newFileName);
                break;
            default:
                break;
        }

        return $newPath;
    }

    private function imageManipulation(UploadedFile $imageAdd)
    {
        $extension = $imageAdd->guessExtension();
        $fileName = md5(uniqid()).'.'.$extension;
        $imagePath = realpath($this->getParameter('kernel.root_dir').'/../web/images/');
        $imageAdd->move($imagePath, $fileName);

        $imageUploaded = $imagePath.'/'.$fileName;
        $rama = imagecreatefrompng($imagePath.'/rame3.png');

        $ramaWidth = imagesx($rama);
        $ramaHeight = imagesy($rama);
        $this->resizeImage($imageUploaded, $imagePath, $fileName, $ramaHeight, $ramaWidth);

        if ($extension == 'gif') {
            $image = imagecreatefromgif($imageUploaded);
        } elseif ($extension == "jpeg" or $extension == "jpg") {
            $image = imagecreatefromjpeg($imageUploaded);
        } elseif ($extension == 'png') {
            $image = imagecreatefrompng($imageUploaded);
        } else {
            die("wrong extension");
        }

        imagecopyresampled($image, $rama, 0, 0, 0, 0, $ramaWidth, $ramaHeight, $ramaWidth, $ramaHeight);

        switch ($extension) {
            case 'png':
                imagepng($image, $imageUploaded);
                break;
            case 'jpeg':
            case 'jpg':
                imagejpeg($image, $imageUploaded, 90);
                break;
            case 'gif':
                imagegif($image, $imageUploaded);
                break;
            default:
                break;
        }

        $response = new BinaryFileResponse($imageUploaded);
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);

        return $response;
    }
}
