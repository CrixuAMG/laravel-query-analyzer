<?php

namespace CrixuAMG\QueryAnalyzer\Exceptions;

use Exception;

class QueryAnalyzerException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  Request
     *
     * @return JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors'  => [],
        ], $this->getCode());
    }
}
