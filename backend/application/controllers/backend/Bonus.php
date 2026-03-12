<?php
class Bonus extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Users_model');
    }


    public function Welcomebonus()
    {
        $data = [
            'title' => 'Welcome Bonus',
            'bonus' => $this->Users_model->Bonus()
        ];
        
        template('bonus/index', $data);
        
    }

    public function Refferalbonus()
    {
        $data = [
            'title' => 'Refferal Bonus',
            'bonus' => $this->Users_model->RefferalBonus()
        ];
        
        template('bonus/index', $data);
        
    }

    public function Welcomerefferalbonus()
    {
        $data = [
            'title' => 'Welcome Refferal Bonus',
            'bonus' => $this->Users_model->WelcomeRefferalBonus()
        ];
        
        template('bonus/index', $data);
        
    }


    public function Purchasebonus()
    {
        $data = [
            'title' => 'Purchase Bonus',
            'bonus' => $this->Users_model->PurchaseBonus()
        ];
        
        template('bonus/index', $data);
        
    }
    

    
}
