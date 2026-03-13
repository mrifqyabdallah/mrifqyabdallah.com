---
title: Welcome to The Community Blog and How You Can Contribute
creator: mrifqyabdallah
tags: [meta, community]
excerpt: The community blog is live. Here's how it works, and how you can submit your own post via a pull request.
---

# Welcome to the Community Blog

This blog is now live and it's open to contributions from anyone.

Every post you read here started as a plain markdown file committed to the repository. There's no CMS, no admin panel, no account required to write. Just a pull request.

## How it works

Posts are stored as markdown files in `resources/blogs/`. When a pull request is merged and the app is deployed, the new post automatically appears on the site. The filename determines the URL slug and the publish date.

## How to contribute

**1. Fork the repository**

Start by forking the repo on GitHub and cloning it locally.

**2. Create your markdown file**

Add a new file to `resources/blogs/` following this naming format:

```
yyyy-mm-dd-your-post-title.md
```

For example:

```
2026-03-11-why-i-love-laravel.md
```

The date becomes the publish date. The rest of the filename becomes the URL slug — so the above would be accessible at `/blog/why-i-love-laravel`.

**3. Add the required frontmatter**

Every post must start with a frontmatter block:

```markdown
---
title: Why I Love Laravel
creator: your-github-username
tags: [laravel, php]
excerpt: A short one or two sentence description of your post.
---

Your content goes here...
```

All five fields are required: `title`, `creator`, `tags`, `excerpt`, and a non-empty body. A CI check will validate these automatically when you open a PR.

**4. Adding images or embedding YouTube**

If your post includes images or GIFs, add them to `public/media/blogs/` and reference them in your markdown:

```markdown
![Description of image](/media/blogs/your-image.png)
```

Keep images reasonably sized. GIFs especially can get large.

To embed a YouTube video in your post, use this syntax anywhere in your content. For example, to embed `https://www.youtube.com/watch?v=dQw4w9WgXcQ`, write:

```markdown
::youtube[dQw4w9WgXcQ]
```

**5. Open a pull request**

Push your branch and open a PR against `main`. The CI check will validate your frontmatter automatically. Once it passes and the PR is reviewed and merged, your post will go live on the next deploy.

## A few guidelines

- Write about things you know and care about. Technical deep-dives, lessons learned, opinions, tutorials; all are welcome.
- Keep the `creator` field as your GitHub username so readers know who to credit.
- Tags help people find related posts. Use existing tags where they fit, and introduce new ones when they genuinely add value.
- There is no minimum or maximum length. Write as much or as little as the topic needs.

## Questions?

Open an issue or start a discussion on GitHub. I'm looking forward to read what you write.
