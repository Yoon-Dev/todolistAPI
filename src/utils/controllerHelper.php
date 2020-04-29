<?php
namespace App\utils;

use App\Entity\Label;
use App\Repository\LabelRepository;

trait controllerHelper
{
    public function isLabeled(?int $id, LabelRepository $labeler): ?Label
    {
        if(!empty($id)){
            return $labeler->find($id);
        }else{
            return null;
        }
    }
}