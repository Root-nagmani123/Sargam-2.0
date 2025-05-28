<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Input extends Component
{

    public $name;
    public $label;
    public $type;
    public $value;
    public $required;
    public $formLabelClass;
    public $formInputClass;
    public $helperSmallText;
    public $labelRequired;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        $name, 
        $label = null, 
        $type = 'text',
        $value = null,
        $required = false,
        $formLabelClass = null,
        $formInputClass = null,
        $helperSmallText = null,
        $labelRequired = false
    )
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->value = $value;
        $this->required = $required;
        $this->formLabelClass = $formLabelClass;
        $this->formInputClass = $formInputClass;
        $this->helperSmallText = $helperSmallText;
        $this->labelRequired = $labelRequired;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.input');
    }
}
