<?php namespace Periskop;

use Illuminate\Support\Fluent;
use Illuminate\Filesystem\Filesystem;
use Imagick;

class ImageRepository {

    protected $filesystem;

    protected $images;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Get all necessary information to an image
     *
     * @param   string $path
     * @return  mixed         Illuminate\Support\Fluent | null
     */
    public function get($path)
    {
        // File not found
        if(!$this->filesystem->exists($path))
        {
            return null;
        }

        //filectime basename
        $image = new Imagick($path);

        $data = array(
            'timestamp' => $this->getCTime($path),
            'name'      => $this->getFilename($path),
            'url'       => asset('uploads/' . $this->getFilename($path)),
            'width'     => $image->getImageWidth(),
            'height'    => $image->getImageHeight(),
        );

        return new Fluent($data);
    }

    /**
     * Get all images from a directory
     *
     * @param  string  $path
     * @return array
     */
    public function getFromDirectory($path)
    {
        $return = array();
        $files = $this->filesystem->files($path);

        foreach ($files as $file)
        {
            $f = $this->get($file);

            if($f !== null)
            {
                $return[] = $f;
            }
        }

        return $return;
    }

    protected function getCTime($path)
    {
        return filectime($path);
    }

    protected function getFilename($path)
    {
        return basename($path);
    }

}