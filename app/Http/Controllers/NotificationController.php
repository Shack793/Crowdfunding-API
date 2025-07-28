<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Test endpoint for debugging
     */
    public function test()
    {
        Log::info('NotificationController@test called', [
            'timestamp' => now(),
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification controller is working',
            'timestamp' => now(),
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::id()
        ]);
    }
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            Log::info('NotificationController@index called', [
                'user_id' => Auth::id(),
                'user_authenticated' => Auth::check(),
                'request_params' => $request->all(),
                'request_url' => $request->fullUrl(),
                'request_method' => $request->method(),
                'timestamp' => now()
            ]);

            if (!Auth::check()) {
                Log::error('User not authenticated in notifications index', [
                    'headers' => $request->headers->all(),
                    'bearer_token_present' => $request->bearerToken() ? 'yes' : 'no'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = Auth::user();
            Log::info('User details in notifications', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name
            ]);

            $query = $user->notifications();

            // Filter by read status
            if ($request->has('unread_only') && $request->unread_only) {
                $query->whereNull('read_at');
                Log::info('Filtering by unread notifications only');
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
                Log::info('Filtering by notification type', ['type' => $request->type]);
            }

            // Limit results
            $limit = $request->get('limit', 50);
            Log::info('Applying limit to notifications', ['limit' => $limit]);

            $notifications = $query->orderBy('created_at', 'desc')
                                  ->limit($limit)
                                  ->get();

            Log::info('Notifications retrieved successfully', [
                'total_count' => $notifications->count(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'debug_info' => [
                    'user_id' => $user->id,
                    'total_notifications' => $notifications->count(),
                    'request_params' => $request->all()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in NotificationController@index', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount()
    {
        try {
            Log::info('NotificationController@getUnreadCount called', [
                'user_id' => Auth::id(),
                'user_authenticated' => Auth::check(),
                'timestamp' => now()
            ]);

            if (!Auth::check()) {
                Log::error('User not authenticated in getUnreadCount');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $count = Auth::user()->unreadNotifications()->count();
            
            Log::info('Unread count retrieved successfully', [
                'user_id' => Auth::id(),
                'unread_count' => $count
            ]);

            return response()->json([
                'success' => true,
                'unread_count' => $count
            ]);

        } catch (\Exception $e) {
            Log::error('Error in NotificationController@getUnreadCount', [
                'error_message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting unread count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead($id)
    {
        try {
            Log::info('NotificationController@markAsRead called', [
                'notification_id' => $id,
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            if (!Auth::check()) {
                Log::error('User not authenticated in markAsRead');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $notification = Auth::user()->notifications()->findOrFail($id);
            $notification->markAsRead();
            
            Log::info('Notification marked as read successfully', [
                'notification_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Notification not found in markAsRead', [
                'notification_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error in NotificationController@markAsRead', [
                'error_message' => $e->getMessage(),
                'notification_id' => $id,
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error marking notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            Log::info('NotificationController@markAllAsRead called', [
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            if (!Auth::check()) {
                Log::error('User not authenticated in markAllAsRead');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            Auth::user()->unreadNotifications->markAsRead();
            
            Log::info('All notifications marked as read successfully', [
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in NotificationController@markAllAsRead', [
                'error_message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error marking all notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific notification
     */
    public function destroy($id)
    {
        try {
            Log::info('NotificationController@destroy called', [
                'notification_id' => $id,
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            if (!Auth::check()) {
                Log::error('User not authenticated in destroy');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $notification = Auth::user()->notifications()->findOrFail($id);
            $notification->delete();
            
            Log::info('Notification deleted successfully', [
                'notification_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Notification not found in destroy', [
                'notification_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error in NotificationController@destroy', [
                'error_message' => $e->getMessage(),
                'notification_id' => $id,
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete all read notifications
     */
    public function deleteRead()
    {
        try {
            Log::info('NotificationController@deleteRead called', [
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            if (!Auth::check()) {
                Log::error('User not authenticated in deleteRead');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $deletedCount = Auth::user()->readNotifications()->delete();
            
            Log::info('Read notifications deleted successfully', [
                'user_id' => Auth::id(),
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} notifications deleted successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Error in NotificationController@deleteRead', [
                'error_message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting read notifications: ' . $e->getMessage()
            ], 500);
        }
    }
}
