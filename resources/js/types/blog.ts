export interface Blog {
    id: number;
    slug: string;
    title: string;
    creator: string;
    excerpt: string;
    tags: string[];
    published_at: string;
    content: string;
}

export interface PaginatedBlogs {
    data: Blog[];
    next_cursor: string | null;
}