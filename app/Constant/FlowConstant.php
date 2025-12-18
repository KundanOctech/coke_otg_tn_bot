<?php

namespace App\Constant;

class FlowConstant
{

    // Flow types
    public static $typeAddUnique = 'unique_code';
    public static $typeClaimForm = 'claim_form';


    // Flow status
    public static $statusAddCodeMatchTicket = 'MATCH-TICKET';
    public static $statusAddCodeMerchandise = 'MERCHANDISE';
    public static $statusAddCodeMassPrizeWinner = 'MASS-PRIZE-WINNER';
    public static $statusAddCodeNotWinner = 'NOT-WINNER';
    public static $statusAddOutsideTimeWindow = 'OUTSIDE_TIME_WINDOW';
    public static $statusAddCodeDailyLimitOver = 'DAILY_LIMIT_OVER';

    public static $statusClaimFormClaimed = 'CLAIMED';

    // Screen name
    public static $screenAddCodeUniqueCode = 'UNIQUE_CODE';
    public static $screenAddCodeOutsideTimeWindow = 'OUTSIDE_TIME_WINDOW';
    public static $screenAddCodeDailyLimitOver = 'DAILY_LIMIT_OVER';
    public static $screenAddCodeMassPrizeWinner = 'WON_PHONEPE';
    public static $screenAddCodeBumperWinner = 'WON_BIKE';
    public static $screenAddCodeNotWinner = 'NOT_WINNER';

    public static $screenClaimFormAdd = 'CLAIM_FORM';
    public static $screenClaimFormSummary = 'CLAIM_FORM';
    public static $screenClaimFormSuccess = 'CLAIM_SUCCESS';

    public static $screenProfile = 'PROFILE';
    public static $screenProfileSummary = 'SUMMARY';

    public static $screenLeadForm = 'LEAD_FORM';

    public static $screenSelectState = 'STATE_SELECTION';
}
