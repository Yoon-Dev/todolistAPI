<?php
namespace App\utils;

use App\Entity\Label;
use App\Repository\LabelRepository;

trait controllerHelper
{
    public function isLabeled(?int $id, LabelRepository $labelrepo): ?Label
    {
        if(!empty($id)){
            return $labelrepo->find($id);
        }else{
            return null;
        }
    }
}