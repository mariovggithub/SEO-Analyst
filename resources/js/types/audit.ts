export type AuditStatus = 'pending' | 'processing' | 'completed' | 'failed';

export type AuditStats = {
    total: number;
    completed: number;
    processing: number;
    avg_score: number | null;
};

export type Audit = {
    id: string;
    url: string;
    domain: string;
    status: AuditStatus;
    status_label: string;
    status_color: string;
    overall_score: number | null;
    created_at: string;
    created_at_full: string;
};

export type CoreWebVitals = {
    fcp: number | null;
    lcp: number | null;
    cls: number | null;
    tbt: number | null;
    si: number | null;
    tti: number | null;
};

export type PerformanceScores = {
    performance: number;
    seo: number;
    best_practices: number;
};

export type Diagnostic = {
    title: string;
    description: string;
    score: number | null;
    displayValue: string | null;
};

export type PerformanceStrategy = {
    scores: PerformanceScores;
    core_web_vitals: CoreWebVitals;
    diagnostics: Diagnostic[];
};

export type PerformanceSummary = {
    mobile: PerformanceStrategy;
    desktop: PerformanceStrategy;
    score: number;
};

export type Technology = {
    name: string;
    categories: string[];
    website: string | null;
    icon: string | null;
    confidence: number | null;
    version: string | null;
};

export type TechStackSummary = {
    technologies: Technology[];
    grouped: Record<string, Technology[]>;
    count: number;
    score: number;
};

export type MetaInfo = {
    title: string;
    title_length: number;
    description: string;
    description_length: number;
    canonical: string;
    has_og_tags: boolean;
    og_tags: Record<string, string>;
};

export type HeadingInfo = {
    count: number;
    texts: string[];
};

export type ImageInfo = {
    total: number;
    without_alt: number;
    alt_coverage: number;
};

export type LinkInfo = {
    internal: number;
    external: number;
    nofollow: number;
    total: number;
};

export type SitemapInfo = {
    exists: boolean;
    url: string;
    status_code: number | null;
};

export type RobotsTxtInfo = {
    exists: boolean;
    url: string;
};

export type OnPageSummary = {
    meta: MetaInfo;
    headings: Record<'h1' | 'h2' | 'h3', HeadingInfo>;
    ssl: boolean;
    sitemap: SitemapInfo;
    robots_txt: RobotsTxtInfo;
    images: ImageInfo;
    links: LinkInfo;
    score: number;
};

export type AuditResultItem = {
    category: string;
    category_label: string;
    summary: PerformanceSummary | TechStackSummary | OnPageSummary;
};

export type AuditResults = Record<string, AuditResultItem>;

