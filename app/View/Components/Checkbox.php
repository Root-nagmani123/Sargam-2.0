<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Checkbox extends Component
{
    public $name;
    public $label;
    public $value;
    public $id;
    public $checked;
    public $class;
    public $formLabelClass;
    public $formCheckboxClass;
    public $formWrapperClass;
    public $options;
    public $selected;
    public $disabled;
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        $name = null,
        $label = null,
        $value = null,
        $id = null,
        $checked = false,
        $class = null,
        $formLabelClass = null,
        $formCheckboxClass = null,
        $formWrapperClass = null,
        $options = [],
        $selected = [],
        $disabled = false
    ) {

        $this->name = $name;
        $this->label = $label;
        $this->value = $value;
        $this->id = $id;
        $this->checked = $checked;
        $this->class = $class;
        $this->formLabelClass = $formLabelClass;
        $this->formCheckboxClass = $formCheckboxClass;
        $this->formWrapperClass = $formWrapperClass;
        $this->options = $options;
        $this->selected = $selected;
        $this->disabled = $disabled;
        
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.checkbox');
    }
}
