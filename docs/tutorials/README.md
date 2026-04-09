# Tutorials Directory

This folder contains structured learning paths for laravel-ai-workflows, from beginner to advanced.

## 📚 Available Tutorials

### 1. [Beginner: Getting Your First Chain Working](./01-beginner-first-chain.md)
**Duration:** 15 minutes

Learn the absolute basics:
- Create your first chain
- Run synchronous and streaming modes
- Understand input/output flows
- Handle basic errors

**Prerequisites:** PHP basics, Laravel familiarity

---

### 2. [Beginner-Intermediate: Memory & Conversation](./02-memory-and-conversation.md)
**Duration:** 20 minutes

Add state to your chains:
- Use conversation memory
- Build a multi-turn chatbot
- Persist memory between requests
- Handle chat history

**Prerequisites:** Complete tutorial 1

---

### 3. [Intermediate: Chains, Composition & Workflows](./03-chains-composition.md)
**Duration:** 25 minutes

Build complex pipelines:
- Chain multiple chains together
- Use ChainFactory for fluent composition
- Understand key flow between chains
- Build a content creation pipeline

**Prerequisites:** Complete tutorial 2

---

### 4. [Intermediate-Advanced: State Graphs & Workflows](./04-state-graphs-workflows.md)
**Duration:** 30 minutes

Master deterministic workflows:
- Create custom state classes
- Build complex DAGs with nodes and edges
- Use conditional routing
- Implement approval workflows

**Prerequisites:** Complete tutorial 3

---

### 5. [Advanced: RAG & Retrieval](./05-advanced-rag.md)
**Duration:** 35 minutes

Build context-aware systems:
- Set up vector stores
- Implement RAG chains
- Use hybrid retrieval
- Combine memory + retrieval

**Prerequisites:** Complete tutorial 4

---

### 6. [Advanced: Queue Workers & Production Patterns](./06-production-patterns.md)
**Duration:** 40 minutes

Deploy to production:
- Queue-safe graph execution
- Checkpointing and resumability
- Error handling and resilience
- Monitoring and observability

**Prerequisites:** Complete tutorial 5

---

## 🎯 Learning Paths

### Path 1: Simple Q&A Bot (2 hours)
1. Tutorial 1: First Chain
2. Tutorial 2: Memory & Conversation
3. → Build a simple chatbot

### Path 2: Content Pipeline (3 hours)
1. Tutorial 1: First Chain
2. Tutorial 3: Chains & Composition
3. → Build a draft → edit → publish pipeline

### Path 3: Complex Workflows (4 hours)
1. Tutorial 1-3: Chains foundations
2. Tutorial 4: State Graphs
3. → Build an approval workflow

### Path 4: Enterprise RAG System (6+ hours)
1. Tutorial 1-4: Foundations
2. Tutorial 5: RAG
3. Tutorial 6: Production patterns
4. → Build a document Q&A system with memory

---

## 🚀 Quick Start per Tutorial

Each tutorial has runnable code samples. To run a tutorial example:

```bash
cd laravel-ai-workflows
php artisan tinker

# Then copy-paste code from the tutorial
```

Or create a command:

```bash
php artisan make:command TutorialCommand
```

Then add the tutorial code to `handle()` method and run:

```bash
php artisan tutorial:run
```

---

## 📝 What You'll Build

By the end of all tutorials, you'll have:

✅ Simple chatbots with memory  
✅ Multi-stage content pipelines  
✅ Conditional workflows with branching  
✅ RAG systems with vector search  
✅ Production-ready error handling  
✅ Queue-safe graph execution  

---

**Ready to start?** → [Begin with Tutorial 1](./01-beginner-first-chain.md) 🚀

