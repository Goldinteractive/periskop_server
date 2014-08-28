<?php

class ImageController extends Controller {

    protected $final_folder;

    protected $mobile_folder;

    public function __construct()
    {
        $this->final_folder = '/' . trim(public_path('images'), '/') . '/';
        $this->mobile_folder = '/' . trim(public_path('images_mobile'), '/') . '/';
    }

    /**
     * If this route is triggerd, it means the mobile
     * image doesn't exist yet. We resize it and save it.
     *
     * @param  string  $name  Imagename of the file in the images folder
     * @return Image
     */
    public function getSmallImage($name)
    {
        $img = Image::make($this->final_folder . $name)->resize(800, 533);
        $img->save($this->mobile_folder . $name, 80);

        return $img->response();
    }

}