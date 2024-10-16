<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteLogo;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;

class AgentLogoController extends Controller
{
    use HttpResponses;

    public function index()
    {
        $player = Auth::user();

        $siteLogo = SiteLogo::where('agent_id', $player->agent_id)->first();
        if ($siteLogo) {
            return $this->success($siteLogo, 'Agent Logo Successfully');
        } else {
            return $this->error(null, 'Agent Logo Not Found', '400');
        }
    }
}
