<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 整改单扣分项
 */
class TicketDeduction extends Model
{
    protected $name = 'ticket_deductions';
    protected $autoWriteTimestamp = false;

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
