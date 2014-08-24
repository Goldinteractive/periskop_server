<?php namespace Periskop;

use Latchet;

class DirectoryObserver {

    protected $images;

    public function __construct(ImageRepository $images)
    {
        $this->images = $images;
    }

    /**
     * A new file was added in an observed folder.
     * The fileurl and other data should be published
     * to our subscribers
     *
     * @param string  $path
     */
    public function newFile($path)
    {
        $image = $this->images->get($path);

        if($image !== null)
        {
            Latchet::publish('stream', $image->toArray());
        }
    }

}