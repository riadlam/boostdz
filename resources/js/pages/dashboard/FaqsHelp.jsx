import { useMemo, useState } from 'react';
import { ChevronDown, Mail, MessageCircle, Search } from 'lucide-react';
import { DashboardPanel } from '../../components/dashboard/DashboardPanel';
import { faqs } from '../../content/landing';
import { cn } from '../../lib/cn';

const faqGroups = faqs.map((g) => g.group);

function FaqItem({ question, answer, open, onToggle }) {
    return (
        <div className="border-b border-[var(--color-dash-border-subtle)] last:border-b-0">
            <button
                type="button"
                onClick={onToggle}
                className="flex w-full items-start justify-between gap-3 px-4 py-3.5 text-left transition hover:bg-[var(--color-dash-row-hover)]"
            >
                <span className="text-sm font-medium">{question}</span>
                <ChevronDown
                    className={cn('mt-0.5 size-4 shrink-0 text-muted-foreground transition-transform', open && 'rotate-180')}
                />
            </button>
            {open ? (
                <div className="px-4 pb-4 text-sm leading-relaxed text-muted-foreground">{answer}</div>
            ) : null}
        </div>
    );
}

export default function FaqsHelp() {
    const [query, setQuery] = useState('');
    const [group, setGroup] = useState('All');
    const [openId, setOpenId] = useState(null);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        return faqs
            .filter((section) => group === 'All' || section.group === group)
            .flatMap((section) =>
                section.items.map((item, index) => ({
                    id: `${section.group}-${index}`,
                    group: section.group,
                    question: item.q,
                    answer: item.a,
                })),
            )
            .filter((item) => {
                if (!q) return true;
                return item.question.toLowerCase().includes(q) || item.answer.toLowerCase().includes(q);
            });
    }, [group, query]);

    return (
        <div className="space-y-4 py-1" data-test-id="faqs-page">
            <div>
                <h1 className="text-xl font-semibold tracking-tight">FAQs &amp; Help</h1>
                <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Answers about delivery, refill, safety, and payments — or reach support anytime.
                </p>
            </div>

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex gap-1 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    {['All', ...faqGroups].map((tab) => (
                        <button
                            key={tab}
                            type="button"
                            onClick={() => setGroup(tab)}
                            className={cn(
                                'inline-flex shrink-0 rounded-lg border px-3 py-1.5 text-xs font-medium transition',
                                group === tab
                                    ? 'border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] text-foreground shadow-sm'
                                    : 'border-transparent text-muted-foreground hover:border-[var(--color-dash-border-subtle)] hover:bg-[var(--color-dash-surface)] hover:text-foreground',
                            )}
                        >
                            {tab}
                        </button>
                    ))}
                </div>

                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted-foreground" />
                    <input
                        type="search"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search questions…"
                        className="h-9 w-full rounded-lg border border-[var(--color-dash-border)] bg-[var(--color-dash-surface)] py-2 pr-3 pl-9 text-sm outline-none placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-primary/30"
                    />
                </div>
            </div>

            <DashboardPanel
                title={`${filtered.length} question${filtered.length === 1 ? '' : 's'}`}
                bodyClassName="p-0"
            >
                {filtered.length === 0 ? (
                    <p className="px-4 py-10 text-center text-sm text-muted-foreground">
                        No questions match your search. Try another keyword or category.
                    </p>
                ) : (
                    filtered.map((item) => (
                        <FaqItem
                            key={item.id}
                            question={item.question}
                            answer={item.answer}
                            open={openId === item.id}
                            onToggle={() => setOpenId((current) => (current === item.id ? null : item.id))}
                        />
                    ))
                )}
            </DashboardPanel>

            <DashboardPanel title="Still have questions?" bodyClassName="dash-panel-body-padded">
                <p className="text-sm text-muted-foreground">
                    Our support team is available around the clock. Most replies land within a few minutes.
                </p>
                <div className="mt-4 grid gap-2 sm:grid-cols-2">
                    <a
                        href="mailto:support@boostdz.com"
                        className="inline-flex items-center justify-center gap-2 rounded-lg border border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)] px-4 py-2.5 text-sm font-medium transition hover:bg-[var(--color-dash-row-hover)]"
                    >
                        <Mail className="size-4" />
                        Email support
                    </a>
                    <button
                        type="button"
                        className="inline-flex items-center justify-center gap-2 rounded-lg border border-[var(--color-dash-border)] bg-[var(--color-dash-canvas)] px-4 py-2.5 text-sm font-medium transition hover:bg-[var(--color-dash-row-hover)]"
                    >
                        <MessageCircle className="size-4" />
                        Live chat
                    </button>
                </div>
            </DashboardPanel>
        </div>
    );
}
