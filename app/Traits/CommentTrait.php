<?php
namespace App\Traits;

use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait CommentTrait
{
    public function showComment($commentId)
    {
        try {
            $comment = Comment::findOrFail($commentId);
            Gate::authorize('viewAny', $comment);
            return new CommentResource($comment);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
