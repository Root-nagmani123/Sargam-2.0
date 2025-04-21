<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Select extends Component
{
    
    public $name;
    public $label;
    public $options;
    public $value;
    public $required;
    public $formLabelClass;
    public $formSelectClass;
    public $multiple;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($name, $label = null, $options = [], $value = null, $required = false, $formLabelClass = null, $formSelectClass = null, $multiple = false)
    {
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
        $this->value = $value;
        $this->required = $required;
        $this->formLabelClass = $formLabelClass;
        $this->formSelectClass = $formSelectClass;
        $this->multiple = $multiple;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select');
    }
}
