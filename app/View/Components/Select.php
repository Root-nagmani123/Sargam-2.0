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
    public $labelRequired;
    public $id;

    /** Caption of the empty first option, e.g. "Select Employee Type". */
    public $placeholder;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($name, $label = null, $options = [], $value = null, $required = false, $formLabelClass = null, $formSelectClass = null, $multiple = false, $labelRequired = false, $id = null, $placeholder = null)
    {
        $this->placeholder = $placeholder;
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
        $this->value = $value;
        $this->required = $required;
        $this->formLabelClass = $formLabelClass;
        $this->formSelectClass = $formSelectClass;
        $this->multiple = $multiple;
        $this->labelRequired = $labelRequired;
        $this->id = $id;
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
