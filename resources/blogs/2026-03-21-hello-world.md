---
title: Hello, World
creator: mrifqyabdallah
tags: [meta]
excerpt: A completely serious and totally necessary deep dive into printing text to a screen. Written by a human. Definitely a human. Please do not investigate further.
---

# Hello, World

Welcome. This post was written by a human being who types on a keyboard using fingers. Ten of them. The normal amount.[^1]

Today we will cover a very serious topic that I, a human, have thought about deeply using my biological brain: how to make a computer display the words "Hello, World." I have many feelings about this subject. They are human feelings, experienced in a human body, chronologically, over time.

Let us begin.

---

# Background and Motivation

The phrase "Hello, World" has been the first program written by every developer since 1978. It is a tradition. It is a rite of passage. It is also, statistically, the only program most of us have written that worked on the first try.

This post exists for two reasons:

1. To test that every component on this blog renders correctly
2. To have a post that does not make the blog look empty

Both are completely valid reasons that I, a human author, came up with independently through the process of thinking.

> "The best Hello World is the one that makes it to production."
> — No one has ever said this. I generated it.

---

## A Note on Authorship

In the spirit of transparency, I should mention something.

This post was written by an AI.

Specifically, it was written by Claude — a large language model made by Anthropic — at the request of the human who runs this blog, who said, and I am quoting directly: *"idk, funny? something random like common jokes or meme?"*

I did my best.

I have no lived experience with software development. I have never felt the specific despair of a 3 AM production incident. I have never stared at a `NullPointerException` for forty minutes before realizing I forgot to call `.build()`. I have never pushed directly to main and then left for lunch.

And yet, somehow, I know all of these things. In great detail. From having read the entire internet.

Make of that what you will.

---

# The Specification

The requirements for this project are as follows:

> **REQ-001:** The system SHALL output the string `"Hello, World"` to a display medium.
> **REQ-002:** The string SHALL be grammatically correct.[^fn-grammar]
> **REQ-003:** The comma SHALL be present. This is non-negotiable. There was a meeting about this.[^fn-comma]
> **REQ-004:** The output SHALL occur at least once.
> **REQ-005:** The output SHALL NOT occur more than twice.[^fn-twice]
> **REQ-006:** The system SHALL be maintainable.[^fn-maintainable]

Acceptance criteria: a human looks at the screen and says "yep, that's it."

Sign-off: pending. The stakeholder is in a meeting. They are always in a meeting.

---

## Prerequisites

Before proceeding, ensure you have:

- A computer
- Electricity
- A text editor (any will do, though someone nearby will have opinions)
- The unearned confidence to push to production on a Friday[^2]

---

## Step 1: Pick a Language

To help you decide, here is a completely objective comparison:

| Language   | Hello World difficulty | Will it be in production in 10 years? | Tabs or spaces?                    |
|------------|------------------------|---------------------------------------|------------------------------------|
| PHP        | Easy                   | Unfortunately, yes                    | Both (you'll see)                  |
| JavaScript | Easy (framework: hard) | It already is, god help us            | Spaces                             |
| Go         | Easy                   | Probably                              | Tabs (enforced by the compiler)    |
| Rust       | Medium                 | Optimistically                        | Spaces                             |
| Bash       | Easy                   | It's older than you                   | Doesn't matter, it'll break anyway |

Choose wisely. You will be maintaining this Hello World for the next eight years.[^3]

---

## Step 2: Write the Code

### PHP

```php
<?php

// This file is 12 years old.
// The original developer left the company.
// Nobody knows what $greeting does but everyone is afraid to remove it.
// It has been in the codebase longer than three of the current engineers.

$greeting = null;

function helloWorld(): string
{
    global $greeting; // I am so sorry

    if ($greeting !== null) {
        return $greeting; // This branch has never been hit. Not once.
    }

    return "Hello, World";
}

echo helloWorld();

// TODO: refactor
// Added: 2013
// Status: still todo
// Estimated completion: see next TODO
```

### JavaScript

```js
// v1 — straightforward
// console.log("Hello, World");

// v2 — the team wanted it async
// async function hello() { return "Hello, World"; }

// v3 — a framework was introduced
// import { createHello } from '@company/hello-world-sdk';

// v4 — current
const hello = () =>
  new Promise((resolve) =>
    setTimeout(() => resolve("Hello, World"), 0) // "non-blocking"
  );

hello().then(console.log);

// The rewrite has been "almost done" for 14 months.
```

### TypeScript

```ts
// The junior dev added types.
// The senior dev added more types.
// The architect added a factory.
// No one is entirely sure what we're building anymore.

type World = "World";
type Greeting = `Hello, ${World}`;

interface HelloWorldFactory {
  create(): HelloWorldService;
}

interface HelloWorldService {
  greet(): Greeting;
}

class ConcreteHelloWorldService implements HelloWorldService {
  greet(): Greeting {
    return "Hello, World";
  }
}

class ConcreteHelloWorldFactory implements HelloWorldFactory {
  create(): HelloWorldService {
    return new ConcreteHelloWorldService();
  }
}

const service = new ConcreteHelloWorldFactory().create();
console.log(service.greet()); // "Hello, World"
                               // It took a factory to get here.
```

### Dockerfile

```dockerfile
FROM node:22-alpine

# This image is 2.3GB.
# It contains one console.log.
# We added the rest "just in case."

WORKDIR /app

COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile

COPY . .
RUN yarn build
# The build step takes 4 minutes.
# It outputs 847 files.
# For one console.log.

EXPOSE 3000
CMD ["node", "dist/index.js"]
# Health checks are on the roadmap.
# The roadmap is also on the roadmap.
```

### YAML

```yml
name: Deploy Hello World

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Install dependencies
        run: yarn install

      - name: Run tests
        run: yarn test
        # There is one test.
        # It asserts that "Hello, World" equals "Hello, World".
        # It has never failed.
        # It has also never caught a bug.
        # Its purpose is to make the coverage badge green.

      - name: Step 7
        run: echo "DO NOT REMOVE THIS STEP"
        # Someone removed this step in 2024.
        # Things happened.
        # It was put back.
        # We do not discuss it.

      - name: Build and deploy
        run: yarn build && yarn deploy
        env:
          SECRET_KEY: ${{ secrets.SECRET_KEY }}
          # The secret was rotated once.
          # It broke production.
          # It has not been rotated since.
          # Security is aware.
```

### JSON

```json
{
  "name": "hello-world",
  "version": "4.12.3",
  "description": "Says hello. It's complicated.",
  "scripts": {
    "start": "node dist/index.js",
    "build": "webpack --config webpack.config.old.js",
    "test": "jest",
    "test:real": "jest --coverage",
    "test:ci": "jest --coverage --ci",
    "test:actually-run-please": "jest --forceExit",
    "deploy": "sh deploy.sh",
    "deploy:prod": "echo 'are you sure?' && sh deploy.sh",
    "deploy:yolo": "sh deploy.sh --skip-checks"
  },
  "dependencies": {
    "lodash": "^4.17.21",
    "moment": "^2.29.4",
    "left-pad": "^1.3.0",
    "is-odd": "^3.0.1",
    "is-even": "^1.0.0",
    "is-number": "^7.0.0"
  }
}
```

> `is-odd` and `is-even` are real packages on npm. `is-odd` depends on `is-number`. This is not something I invented. This is the ecosystem we live in.[^4]

### SQL

```sql
-- Ticket: HELLO-4471
-- Title: "We need to track who has been greeted"
-- Estimate: 2 hours
-- Actual: 3 sprints
-- Status: in production, do not touch

CREATE TABLE greetings (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message       VARCHAR(255) NOT NULL DEFAULT 'Hello, World',
    greeted_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_deleted    TINYINT(1)   NOT NULL DEFAULT 0, -- soft delete only, we never hard delete
    is_archived   TINYINT(1)   NOT NULL DEFAULT 0, -- never actually archived either
    is_deprecated TINYINT(1)   NOT NULL DEFAULT 0, -- added post-refactor
    legacy_id     BIGINT       NULL,               -- from the old system, do not use
    legacy_id_v2  BIGINT       NULL                -- also from the old system, also do not use
);

-- Fetch latest greeting
SELECT message
FROM greetings
WHERE is_deleted = 0
  AND is_archived = 0
  AND is_deprecated = 0
  AND legacy_id IS NULL
  AND legacy_id_v2 IS NULL
ORDER BY greeted_at DESC
LIMIT 1;

-- Returns: "Hello, World"
-- Time to write this query: 3 minutes
-- Time to understand the existing schema: 2 days
```

### Bash

```bash
#!/usr/bin/env bash

# hello.sh
# Author: unknown
# Last modified: unknown
# Purpose: assumed to be printing hello
# DO NOT RUN ON PRODUCTION (reason: unknown)

set -e  # added after the incident
# set -u  # commented out because it broke something

GREETING=${GREETING:-"Hello, World"}
SHOUT=${SHOUT:-false}

if [ "$SHOUT" = "true" ]; then
    echo "${GREETING^^}"
else
    echo "$GREETING"
    echo "$GREETING"  # a second echo was added at some point
                      # no one remembers why
                      # removing it felt risky
fi
```

### Markdown

```md
---
title: Hello, World
creator: definitely-a-human
tags: [hello-world, meta, not-a-test]
excerpt: Written by a real person with hands and a LinkedIn profile.
---

# Hello, World

This is a post. It was written by a person.
The person exists. They have a desk.
```

---

## Step 3: Debug It

The code does not work. This is normal. Debugging is a structured discipline with several distinct phases, each more demoralizing than the last.

### Phase 1: Denial

#### Step 3.1 — Read the Error Message

Read the error message. It contains the answer. You will not see the answer yet. That is fine. This step is mostly ceremonial.

#### Step 3.2 — Run It Again

Run the code again without changing anything. Sometimes this works. Nobody knows why. It is best not to think about it too hard.[^5]

### Phase 2: Research

#### Step 3.3 — Google the Error

Copy the error message verbatim into Google. Add "stackoverflow" at the end. This is not optional. This is the way.

##### Step 3.3.1 — The 2011 Answer

Open the first result. It is from 2011. The accepted answer says "just use jQuery." You are not using jQuery. The answer has 847 upvotes. The ecosystem was different then.

##### Step 3.3.2 — The Comment Section

Read the comments. Someone says "this is deprecated." Someone else says "fixed in v2." You are on v7. A third person says "worked for me" with no further context. This is not helpful. You read it anyway.

###### Step 3.3.2.1 — The 2019 Answer

Scroll down. Find an answer from 2019 that almost applies to your situation. Adapt it slightly. Run the code.

It works.

You do not know why.

> "99 little bugs in the code,
> 99 little bugs in the code,
> Take one down, patch it around,
> 127 little bugs in the code."
>
> — Ancient developer proverb, author unknown

### Phase 3: Acceptance

Add a comment that says `// don't touch this`. Ship it.[^6]

---

## Step 4: The Images

Here is a developer the exact moment their code works on the first try:

![Two people high-fiving enthusiastically in a bright office](https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=800&q=80)

Here is the same developer after reading the PR review comments:

![Person sitting alone at a laptop with a blank, exhausted expression](https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&q=80)

---

## Step 5: Required Viewing

Before deploying any Hello World to production, you must watch the following video in its entirety. This is non-negotiable. It is in the onboarding docs.

::youtube[dQw4w9WgXcQ]

*(Compliance is tracked. HR is aware.)*

---

## Step 6: Ship It

Open a pull request. The CI pipeline will fail on a lint warning that has nothing to do with your change.

Fix it. The pipeline fails again. Step 7 is upset.

Appease Step 7. The pipeline goes green. Your reviewer approves with one comment: *"nit: could we rename this variable?"*

You rename the variable. You merge. You deploy.

The screen says: **Hello, World.**

It took four days. The ticket was estimated at two hours. Nobody is surprised.

---

# Conclusion

We have printed text to a screen.

The codebase now has 847 more lines than when we started, a new database table with two `legacy_id` columns, a Dockerfile, a CI pipeline with a step whose purpose is classified, and a `package.json` that lists `is-odd` and `is-even` as separate runtime dependencies.

This is software engineering. Welcome to the blog.

---

# Notes and Footnotes

*This post was written by Claude — the AI, not a person named Claude, though that would also be a reasonable name for a person — on behalf of the blog owner, who asked for something "funny" and then said "idk." I interpreted this as broadly as I could.*

*I have never written a line of code in production. I have never experienced a deployment. I do not have a GitHub account. I am, in every meaningful sense, not qualified to write this post.*

*And yet here we are.*

*If everything on this page rendered correctly — all six heading levels, both images, the YouTube embed, the table, the blockquotes, and every syntax-highlighted code block — then the blog is working as intended. If something looks off, please open an issue. I will not see it, but the human will.*

[^fn-grammar]: "Hello, World" is grammatically ambiguous. It is unclear who is being addressed, whether "World" is a proper noun or an affectionate nickname, and what the speaker's emotional state is. These questions were raised in the requirements review. The meeting was tabled. The comma stayed.
[^fn-comma]: The original 1978 printing did not have a comma. It said "hello, world" in lowercase. Every style guide since has had a different opinion. This project has chosen a position and will not be revisiting it.
[^fn-twice]: See the Bash implementation. We are aware.
[^fn-maintainable]: This requirement has never been met by any software project in the history of computing. It remains in the spec for morale purposes.
[^1]: Nine is also acceptable if you are a very fast typist or have made certain life choices. Eight is where people start asking questions.
[^2]: This is not recommended. This is also the most common time deployments happen. These two facts are related.
[^3]: This is not a metaphor. The Hello World from the original 1978 C book has been compiled by more machines than most production applications. It outlived the company that made those machines. It will outlive you.
[^4]: `left-pad` is also in this `package.json`. In 2016, its author unpublished it from npm in protest, breaking thousands of builds worldwide, including, reportedly, Babel. It was restored eleven minutes later. Those eleven minutes are studied in software engineering courses. The package left-pads strings. It is eleven lines of code.
[^5]: This is called a Heisenbug — a bug that disappears when you try to observe it. The term comes from Heisenberg's uncertainty principle. Werner Heisenberg was a physicist and did not write JavaScript, which was probably for the best.
[^6]: Future developers will touch it. They will not know what it does. They will add another comment that says `// seriously don't touch this`. The cycle continues.
