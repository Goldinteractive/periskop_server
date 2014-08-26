<?php namespace Periskop;

class ImageStack {

    /**
     * The images currently on the stack
     *
     * @var array
     */
    protected $stack = array();

    /**
     * Add new image on top of the stack
     *
     * @param  string  $image
     * @return void
     */
    public function push($image)
    {
        array_unshift($this->stack, $image);
    }

    /**
     * Remove and return the top most image
     * from the stack
     *
     * @return mixed  Either the image path or null
     */
    public function pop()
    {
        if ($this->isEmpty())
        {
            return null;
        }

        return array_shift($this->stack);
    }

    /**
     * Get the top most image but don't remove it
     *
     * @return string
     */
    public function top()
    {
        return current($this->stack);
    }

    /**
     * Check if the stack is empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->stack);
    }
}