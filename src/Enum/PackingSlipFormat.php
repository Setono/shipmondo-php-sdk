<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Enum;

enum PackingSlipFormat: string
{
    case A4Pdf = 'a4_pdf';
    case Pdf10x19 = '10x19_pdf';
}
