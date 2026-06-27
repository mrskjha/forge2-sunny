import { Clock, Loader2, CheckCircle2, Archive, AlertTriangle, ArrowUp, ArrowDown, Minus, Flame } from 'lucide-react';

const statusConfig = {
  open: {
    label: 'Open',
    icon: Clock,
    classes: 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-400/30',
  },
  in_progress: {
    label: 'In Progress',
    icon: Loader2,
    classes: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-400/30',
  },
  resolved: {
    label: 'Resolved',
    icon: CheckCircle2,
    classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-400/30',
  },
  closed: {
    label: 'Closed',
    icon: Archive,
    classes: 'bg-gray-100 text-gray-600 ring-gray-500/20 dark:bg-gray-700 dark:text-gray-400 dark:ring-gray-400/30',
  },
};

const priorityConfig = {
  low: {
    label: 'Low',
    icon: ArrowDown,
    classes: 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-400/30',
  },
  medium: {
    label: 'Medium',
    icon: Minus,
    classes: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400 dark:ring-yellow-400/30',
  },
  high: {
    label: 'High',
    icon: ArrowUp,
    classes: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-400/30',
  },
  urgent: {
    label: 'Urgent',
    icon: Flame,
    classes: 'bg-red-100 text-red-800 ring-red-600/30 dark:bg-red-500/20 dark:text-red-400 dark:ring-red-400/40',
  },
};

export function StatusBadge({ status }) {
  const config = statusConfig[status] || statusConfig.open;
  const Icon = config.icon;
  return (
    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset ${config.classes}`}>
      <Icon className="w-3 h-3" />
      {config.label}
    </span>
  );
}

export function PriorityBadge({ priority }) {
  const config = priorityConfig[priority] || priorityConfig.medium;
  const Icon = config.icon;
  return (
    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset ${config.classes}`}>
      <Icon className="w-3 h-3" />
      {config.label}
    </span>
  );
}

export function SlaBadge({ dueAt }) {
  if (!dueAt) return null;
  const due = new Date(dueAt);
  const breached = due < new Date();
  const Icon = breached ? AlertTriangle : Clock;
  const label = breached
    ? 'SLA BREACHED'
    : `SLA due ${due.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`;

  return (
    <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold ring-1 ring-inset ${
      breached
        ? 'bg-red-100 text-red-800 ring-red-600/30 animate-pulse dark:bg-red-500/20 dark:text-red-400 dark:ring-red-400/40'
        : 'bg-slate-50 text-slate-600 ring-slate-500/20 dark:bg-slate-700/50 dark:text-slate-400 dark:ring-slate-400/30'
    }`}>
      <Icon className="w-3.5 h-3.5" />
      {label}
    </span>
  );
}
