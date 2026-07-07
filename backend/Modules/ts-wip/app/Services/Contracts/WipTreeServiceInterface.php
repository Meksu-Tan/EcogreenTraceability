<?php

declare(strict_types=1);

namespace Modules\TsWip\Services\Contracts;

interface WipTreeServiceInterface
{
    public function getTree(?string $idPlant): array;
}
