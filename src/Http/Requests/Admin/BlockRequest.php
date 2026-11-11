<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Simtabi\Laranail\WaitlistKit\Enums\BlockType;
use Simtabi\Laranail\WaitlistKit\Validation\BlockRules;

final class BlockRequest extends AdminRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return BlockRules::rules(BlockType::tryFrom($this->string('type')->toString()));
    }

    public function blockType(): BlockType
    {
        return BlockType::from($this->string('type')->toString());
    }
}
