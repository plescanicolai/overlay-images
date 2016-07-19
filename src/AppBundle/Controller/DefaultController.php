<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 * @package AppBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }

    /**
     * @Route("last", name="last_bloc")
     */
    public function latestBlocAction()
    {
        return $this->render('default/last_bloc.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }

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
            /** @var UploadedFile $image1 */
            $image1 = $data['horizontal'];
            $extension = $image1->guessExtension();
            $fileName = md5(uniqid()).'.'.$extension;
            $image1->move(realpath($this->getParameter('kernel.root_dir').'/../web/images/'), $fileName);

            $imageUploaded = realpath($this->getParameter('kernel.root_dir').'/../web/images/'.$fileName);
            $rama = imagecreatefrompng(realpath($this->getParameter('kernel.root_dir').'/../web/images/rame1.png'));
            $ramaWidth = imagesx($rama);
            $ramaHeight = imagesy($rama);
            if ($extension == 'gif') {
                $image = imagecreatefromgif($imageUploaded);
            } elseif ($extension == "jpeg" or $extension == "jpg") {
                $image = imagecreatefromjpeg($imageUploaded);
            } elseif ($extension == 'png') {
                $image = imagecreatefrompng($imageUploaded);
            } else {
                die("wrong extension");
            }
            $size = getimagesize($imageUploaded);
            imagecopyresampled($image, $image, 0, 0, 0, 0, $ramaWidth, $ramaHeight, $ramaWidth, $ramaHeight);
            imagecopyresampled($image, $rama, $size[0] - $ramaWidth - 200, $size[1] - $ramaHeight, 0, 0, $ramaWidth, $ramaHeight, $ramaWidth, $ramaHeight);

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
//            imagejpeg($image, $imageUploaded, 90);

            $response = new BinaryFileResponse($imageUploaded);
            $response->headers->set('Content-Type', 'image/png');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);

            return $response;
        }

        return $this->render('default/image.html.twig', ['horizontalForma' => $horizontalForm->createView(),
                'verticalForm' => $verticalForm->createView(),
        ]);
    }

    public function resizeImage($image, $newPath, $name, $height = 0, $width = 0)
    {
        $imageDetails = $this->getImageDetails($image);
        $heightOrig = $imageDetails->height;
        $widthOrig = $imageDetails->width;
        $fileExtention = $imageDetails->extension;
        $ratio = $imageDetails->ratio;
        $jpegQuality = 75;

        //Resize dimensions are bigger than original image, stop processing
        if ($width > $widthOrig && $height > $heightOrig) {
            return false;
        }

        if ($height > 0) {
            $width = $height * $ratio;
        } elseif ($width > 0) {
            $height = $width / $ratio;
        }
        $width = round($width);
        $height = round($height);

        $gdImageDest = imagecreatetruecolor($width, $height);
        $gdImageSrc = null;
        switch ($fileExtention) {
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

        $newFileName = $newPath.'/'.$name.".".$fileExtention;

        switch ($fileExtention) {
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
}
