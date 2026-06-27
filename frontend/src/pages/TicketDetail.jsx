import { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { api } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import { StatusBadge, PriorityBadge, SlaBadge } from '../components/Badge';
import { MessageSquare, History as HistoryIcon } from 'lucide-react';

function timeAgo(dateStr) {
  const date = new Date(dateStr);
  const diff = Date.now() - date.getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'just now';
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  const days = Math.floor(hrs / 24);
  if (days < 30) return `${days}d ago`;
  return date.toLocaleDateString();
}

export default function TicketDetail() {
  const { id } = useParams();
  const { user } = useAuth();
  const [ticket, setTicket] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [reply, setReply] = useState('');
  const [replying, setReplying] = useState(false);
  const [replyError, setReplyError] = useState('');
  const [statusUpdating, setStatusUpdating] = useState(false);
  const [activeTab, setActiveTab] = useState('conversation');

  const loadTicket = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await api.getTicket(id);
      setTicket(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    loadTicket();
  }, [loadTicket]);

  const handleReply = async (e) => {
    e.preventDefault();
    if (!reply.trim()) return;
    setReplying(true);
    setReplyError('');
    try {
      const newReply = await api.addReply(id, reply);
      setTicket((prev) => ({
        ...prev,
        replies: [...(prev.replies || []), newReply],
      }));
      setReply('');
    } catch (err) {
      setReplyError(err.message);
    } finally {
      setReplying(false);
    }
  };

  const handleStatusChange = async (newStatus) => {
    setStatusUpdating(true);
    try {
      const updated = await api.updateTicket(id, { status: newStatus });
      setTicket((prev) => ({ ...prev, status: updated.status }));
    } catch (err) {
      setError(err.message);
    } finally {
      setStatusUpdating(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <p className="text-gray-400 text-sm">Loading ticket…</p>
      </div>
    );
  }

  if (error && !ticket) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <p className="text-red-500 text-sm mb-3">{error}</p>
        <Link to="/" className="text-indigo-600 dark:text-indigo-400 text-sm font-medium hover:underline">← Back to Dashboard</Link>
      </div>
    );
  }

  if (!ticket) return null;

  const isAdmin = user?.role === 'admin' || user?.role === 'agent';
  const statuses = ['open', 'in_progress', 'resolved', 'closed'];

  return (
    <div className="max-w-4xl mx-auto space-y-5">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-sm">
        <Link to="/" className="text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400">Dashboard</Link>
        <span className="text-gray-300">/</span>
        <span className="text-gray-700 dark:text-gray-300 font-medium">#{ticket.id}</span>
      </div>

      {/* Ticket Header */}
      <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <div className="flex items-start justify-between gap-4 flex-wrap">
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-3 mb-2 flex-wrap">
              <span className="text-xs font-mono text-gray-400">#{ticket.id}</span>
              <StatusBadge status={ticket.status} />
              <PriorityBadge priority={ticket.priority} />
              {ticket.sla_due_at && <SlaBadge dueAt={ticket.sla_due_at} />}
            </div>
            <h1 className="text-xl font-bold text-gray-900 dark:text-white">{ticket.subject}</h1>
          </div>

          {/* Status dropdown */}
          {isAdmin && (
            <div className="flex items-center gap-2">
              <label className="text-xs text-gray-400">Set Status:</label>
              <select
                value={ticket.status}
                onChange={(e) => handleStatusChange(e.target.value)}
                disabled={statusUpdating}
                className="text-sm px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                {statuses.map((s) => (
                  <option key={s} value={s}>
                    {s === 'in_progress' ? 'In Progress' : s.charAt(0).toUpperCase() + s.slice(1)}
                  </option>
                ))}
              </select>
            </div>
          )}
        </div>

        {/* Meta row */}
        <div className="flex items-center gap-4 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/50 text-sm text-gray-500 dark:text-gray-400">
          <span>👤 {ticket.requester?.name || 'Unknown'}</span>
          {ticket.assignee && <span>🎯 {ticket.assignee.name}</span>}
          <span>🕐 {timeAgo(ticket.created_at)}</span>
          {ticket.tags?.length > 0 && (
            <div className="flex items-center gap-1">
              {ticket.tags.map((tag) => (
                <span key={tag} className="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs">{tag}</span>
              ))}
            </div>
          )}
        </div>

        {/* Description */}
        <div className="mt-4 text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
          {ticket.description}
        </div>
      </div>

      {/* Tabbed section: Conversation / History */}
      <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        {/* Tab bar */}
        <div className="flex border-b border-gray-200 dark:border-gray-700">
          <button
            onClick={() => setActiveTab('conversation')}
            className={`flex items-center gap-1.5 px-5 py-3 text-sm font-medium transition ${
              activeTab === 'conversation'
                ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-400'
                : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
            }`}
          >
            <MessageSquare className="w-4 h-4" />
            Conversation
            {ticket.replies?.length > 0 && <span className="text-gray-400 font-normal">({ticket.replies.length})</span>}
          </button>
          <button
            onClick={() => setActiveTab('history')}
            className={`flex items-center gap-1.5 px-5 py-3 text-sm font-medium transition ${
              activeTab === 'history'
                ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-400'
                : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
            }`}
          >
            <HistoryIcon className="w-4 h-4" />
            History
            {ticket.activities?.length > 0 && <span className="text-gray-400 font-normal">({ticket.activities.length})</span>}
          </button>
        </div>

        {/* Conversation Tab */}
        {activeTab === 'conversation' && (
          <>
            {ticket.replies?.length === 0 ? (
              <div className="p-6 text-center text-sm text-gray-400">No replies yet. Start the conversation below.</div>
            ) : (
              <div className="divide-y divide-gray-100 dark:divide-gray-700/50">
                {ticket.replies?.map((r) => (
                  <div key={r.id} className="px-5 py-4 flex gap-3">
                    {/* Avatar */}
                    <div className="shrink-0">
                      <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold text-white ${
                        r.user?.id === user?.id ? 'bg-indigo-500' : 'bg-gray-400 dark:bg-gray-600'
                      }`}>
                        {r.user?.name?.charAt(0)?.toUpperCase() || '?'}
                      </div>
                    </div>
                    {/* Content */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-1">
                        <span className="text-sm font-medium text-gray-900 dark:text-white">{r.user?.name || 'Unknown'}</span>
                        {r.user?.role && (
                          <span className="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-500 capitalize">{r.user.role}</span>
                        )}
                        <span className="text-xs text-gray-400">{timeAgo(r.created_at)}</span>
                      </div>
                      <p className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{r.body}</p>
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* Reply box */}
            <div className="border-t border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/40">
              {replyError && (
                <div className="mb-2 text-sm text-red-600 dark:text-red-400">{replyError}</div>
              )}
              <form onSubmit={handleReply}>
                <textarea
                  value={reply}
                  onChange={(e) => setReply(e.target.value)}
                  rows={3}
                  placeholder="Write a reply…"
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white outline-none resize-none text-sm"
                />
                <div className="flex justify-end mt-2">
                  <button
                    type="submit"
                    disabled={replying || !reply.trim()}
                    className="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {replying ? 'Sending…' : 'Reply'}
                  </button>
                </div>
              </form>
            </div>
          </>
        )}

        {/* History Tab */}
        {activeTab === 'history' && (
          <div className="p-5">
            {ticket.activities?.length === 0 ? (
              <div className="text-center text-sm text-gray-400 py-8">No activity logged yet.</div>
            ) : (
              <div className="relative">
                {/* Timeline line */}
                <div className="absolute left-4 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700" />
                <div className="space-y-4">
                  {ticket.activities?.map((activity) => (
                    <div key={activity.id} className="relative flex items-start gap-4 pl-2">
                      {/* Dot */}
                      <div className="relative z-10 w-4 h-4 mt-0.5 rounded-full bg-indigo-500 ring-4 ring-white dark:ring-gray-800 shrink-0" />
                      {/* Content */}
                      <div className="flex-1">
                        <p className="text-sm text-gray-700 dark:text-gray-300 font-medium">{activity.action}</p>
                        <p className="text-xs text-gray-400 mt-0.5">
                          {activity.user?.name || 'System'} · {timeAgo(activity.created_at)}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
