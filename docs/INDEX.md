# Documentation Index

Quick reference for all documentation files.

## 📚 Main Documentation

| Document | Purpose | Duration | Level |
|----------|---------|----------|-------|
| [README](./README.md) | Welcome, overview, quick start | 5 min | All |
| [01-getting-started](./01-getting-started.md) | Installation, first chain, setup | 20 min | Beginner |
| [02-core-concepts](./02-core-concepts.md) | Foundational concepts & terminology | 15 min | Beginner |
| [03-chains-guide](./03-chains-guide.md) | Chain building, composition, patterns | 25 min | Intermediate |
| [04-state-graphs](./04-state-graphs.md) | Workflows, nodes, routing, branching | 30 min | Intermediate |
| [05-memory-systems](./05-memory-systems.md) | Conversation memory, persistence | 20 min | Intermediate |
| [06-retrieval-rag](./06-retrieval-rag.md) | RAG implementation, vector search | 30 min | Advanced |
| [07-advanced-patterns](./07-advanced-patterns.md) | Production patterns, queues, monitoring | 35 min | Advanced |
| [08-api-reference](./08-api-reference.md) | Complete API reference & contracts | 30 min | Reference |
| [DOCUMENTATION-SUMMARY](./DOCUMENTATION-SUMMARY.md) | Overview of all docs | 10 min | All |

## 🎓 Tutorials

| Tutorial | Topic | Duration | Level |
|----------|-------|----------|-------|
| [01-beginner-first-chain](./tutorials/01-beginner-first-chain.md) | Your first chain | 15 min | Beginner |
| [02-memory-and-conversation](./tutorials/02-memory-and-conversation.md) | Multi-turn chatbots | 20 min | Beginner |
| [03-chains-composition](./tutorials/03-chains-composition.md) | Pipeline building | 25 min | Intermediate |
| [04-state-graphs-workflows](./tutorials/04-state-graphs-workflows.md) | Complex workflows | 30 min | Intermediate |
| [05-advanced-rag](./tutorials/05-advanced-rag.md) | RAG systems | 35 min | Advanced |
| [06-production-patterns](./tutorials/06-production-patterns.md) | Production deployment | 40 min | Advanced |
| [tutorials/README](./tutorials/README.md) | Tutorial index & paths | 5 min | All |

## 🎯 Topic Quick Links

### Installation & Setup
- [Getting Started - Installation](./01-getting-started.md#installation)
- [Getting Started - Configuration](./01-getting-started.md#your-first-chain)

### Chains
- [Chains Guide - Creating Chains](./03-chains-guide.md#creating-chains)
- [Chains Guide - Chaining](./03-chains-guide.md#chaining-chains)
- [Chains Guide - Execution](./03-chains-guide.md#execution-modes)
- [Core Concepts - Chains vs Graphs](./02-core-concepts.md#1-chains-vs-state-graphs)

### State Graphs
- [State Graphs - Core Concepts](./04-state-graphs.md#core-concepts)
- [State Graphs - Building](./04-state-graphs.md#building-a-graph)
- [State Graphs - Routing](./04-state-graphs.md#routing--conditional-edges)
- [State Graphs - Examples](./04-state-graphs.md#real-world-examples)

### Memory
- [Memory Systems - Types](./05-memory-systems.md#memory-types)
- [Memory Systems - Usage](./05-memory-systems.md#using-memory-with-chains)
- [Memory Systems - Persistence](./05-memory-systems.md#using-memory-with-state-graphs)
- [Memory Systems - Advanced](./05-memory-systems.md#advanced-custom-memory-implementation)

### Retrieval & RAG
- [Retrieval - What is RAG](./06-retrieval-rag.md#what-is-rag)
- [Retrieval - Retrievers](./06-retrieval-rag.md#retriever-types)
- [Retrieval - Building RAG](./06-retrieval-rag.md#building-a-rag-chain)
- [Retrieval - Advanced](./06-retrieval-rag.md#advanced-rag-patterns)

### Error Handling
- [Advanced Patterns - Error Handling](./07-advanced-patterns.md#error-handling--resilience)
- [Chains Guide - Error Handling](./03-chains-guide.md#error-handling)

### Production
- [Advanced Patterns - Queues](./07-advanced-patterns.md#queue-based-graph-execution)
- [Advanced Patterns - Checkpointing](./07-advanced-patterns.md#job-state-checkpointing)
- [Advanced Patterns - Monitoring](./07-advanced-patterns.md#observability--logging)
- [Advanced Patterns - Performance](./07-advanced-patterns.md#performance-optimization)

### API Reference
- [API - Chain](./08-api-reference.md#chain)
- [API - ChainFactory](./08-api-reference.md#chainfactory)
- [API - StateGraph](./08-api-reference.md#stategraph)
- [API - State](./08-api-reference.md#state)
- [API - Prompt Template](./08-api-reference.md#prompttemplate)
- [API - Memory](./08-api-reference.md#memory)
- [API - Retriever](./08-api-reference.md#retriever)

## 🔍 Search by Problem

### "How do I...?"

| Question | Answer |
|----------|--------|
| Install laravel-ai-chain? | [Getting Started - Installation](./01-getting-started.md#installation) |
| Create my first chain? | [Tutorial 1](./tutorials/01-beginner-first-chain.md) |
| Add memory to a chain? | [Memory Systems](./05-memory-systems.md) or [Tutorial 2](./tutorials/02-memory-and-conversation.md) |
| Chain multiple chains? | [Chains Guide - Chaining](./03-chains-guide.md#chaining-chains) or [Tutorial 3](./tutorials/03-chains-composition.md) |
| Build conditional workflows? | [State Graphs](./04-state-graphs.md) or [Tutorial 4](./tutorials/04-state-graphs-workflows.md) |
| Implement RAG? | [Retrieval & RAG](./06-retrieval-rag.md) or [Tutorial 5](./tutorials/05-advanced-rag.md) |
| Deploy to production? | [Advanced Patterns](./07-advanced-patterns.md) or [Tutorial 6](./tutorials/06-production-patterns.md) |
| Stream responses? | [Chains Guide - Streaming](./03-chains-guide.md#streaming-execution) |
| Handle errors? | [Advanced Patterns - Error Handling](./07-advanced-patterns.md#error-handling--resilience) |
| Monitor workflows? | [Advanced Patterns - Observability](./07-advanced-patterns.md#observability--logging) |

## 🏆 Learning Paths

### 30-Minute Quick Start
1. [README](./README.md) (5 min)
2. [Getting Started](./01-getting-started.md) (15 min)
3. [Tutorial 1](./tutorials/01-beginner-first-chain.md) (10 min)

### 2-Hour Beginner Path
1. [Getting Started](./01-getting-started.md)
2. [Core Concepts](./02-core-concepts.md)
3. [Tutorial 1](./tutorials/01-beginner-first-chain.md)
4. [Tutorial 2](./tutorials/02-memory-and-conversation.md)

### 3-Hour Intermediate Path
1. [Getting Started](./01-getting-started.md)
2. [Core Concepts](./02-core-concepts.md)
3. [Chains Guide](./03-chains-guide.md)
4. [Tutorial 1-3](./tutorials/)
5. [Memory Systems](./05-memory-systems.md)

### 5-Hour Advanced Path
1. All core guides (1-4)
2. [Retrieval & RAG](./06-retrieval-rag.md)
3. [Advanced Patterns](./07-advanced-patterns.md)
4. All tutorials

### Full Mastery Path (6+ Hours)
1. All guides sequentially
2. All tutorials sequentially
3. [API Reference](./08-api-reference.md) for reference

## 📖 Reading Recommendations by Role

### **I'm a Developer**
Start: [Getting Started](./01-getting-started.md) → [Chains Guide](./03-chains-guide.md)

### **I'm a DevOps Engineer**
Start: [Advanced Patterns](./07-advanced-patterns.md) → [Core Concepts](./02-core-concepts.md)

### **I'm a Data Scientist**
Start: [Retrieval & RAG](./06-retrieval-rag.md) → [State Graphs](./04-state-graphs.md)

### **I'm a Startup Founder**
Start: [Getting Started](./01-getting-started.md) → [Tutorial 2](./tutorials/02-memory-and-conversation.md) (build chatbot quickly)

### **I'm a Technical Manager**
Start: [Core Concepts](./02-core-concepts.md) → [Advanced Patterns](./07-advanced-patterns.md) (understand production)

## 🔑 Key Concepts Glossary

| Term | Where to Learn |
|------|-----------------|
| Chain | [Core Concepts](./02-core-concepts.md#1-chains-vs-state-graphs) |
| State | [Core Concepts](./02-core-concepts.md#2-immutable-state) |
| Immutability | [Core Concepts](./02-core-concepts.md#2-immutable-state) |
| Prompt Template | [Core Concepts](./02-core-concepts.md#3-prompt-templates) |
| Agent | [Core Concepts](./02-core-concepts.md#4-agents--providers) |
| Memory | [Core Concepts](./02-core-concepts.md#5-memory) |
| RAG | [Core Concepts](./02-core-concepts.md#6-retrieval--rag) |
| State Graph | [Core Concepts](./02-core-concepts.md#1-chains-vs-state-graphs) |
| Node | [State Graphs](./04-state-graphs.md#core-concepts) |
| Edge | [State Graphs](./04-state-graphs.md#core-concepts) |
| Conditional Edge | [State Graphs](./04-state-graphs.md#routing--conditional-edges) |
| Retriever | [Retrieval & RAG](./06-retrieval-rag.md#retriever-types) |
| Vector Store | [Retrieval & RAG](./06-retrieval-rag.md#step-1-create-vector-store) |

## 📺 Examples by Use Case

### Chatbots
- [Tutorial 2](./tutorials/02-memory-and-conversation.md)
- [Memory Systems](./05-memory-systems.md)
- [Chains Guide - Example 2](./03-chains-guide.md#example-2-question-answering-with-memory)

### Content Creation
- [Chains Guide - Example 1](./03-chains-guide.md#example-1-content-creation-pipeline)
- [Tutorial 3](./tutorials/03-chains-composition.md#practical-example-email-campaign-generator)
- [Advanced Patterns](./07-advanced-patterns.md#step-5-real-world-complete-system)

### Data Processing
- [State Graphs - Example 1](./04-state-graphs.md#example-1-data-validation-pipeline)
- [Tutorial 4](./tutorials/04-state-graphs-workflows.md#step-1-your-first-state-graph)

### Approval Workflows
- [State Graphs - Example 2](./04-state-graphs.md#example-2-multi-step-approval-workflow)
- [Tutorial 4](./tutorials/04-state-graphs-workflows.md#step-5-real-world-approval-workflow)

### Q&A Systems (RAG)
- [Retrieval & RAG](./06-retrieval-rag.md#step-7-real-world-documentation-qa)
- [Tutorial 5](./tutorials/05-advanced-rag.md#step-7-real-world-documentation-qa)

## 🆘 Getting Help

### Stuck on a concept?
1. Check [Core Concepts](./02-core-concepts.md)
2. See relevant guide section
3. Review related tutorial

### Looking for code example?
1. Check [relevant guide](./03-chains-guide.md#practical-examples)
2. Review [tutorials](./tutorials/)
3. Consult [API Reference](./08-api-reference.md)

### Debugging an issue?
1. Check [Common Issues](./01-getting-started.md#common-issues--solutions) sections
2. Review [Error Handling](./07-advanced-patterns.md#error-handling--resilience)
3. See [Advanced Patterns](./07-advanced-patterns.md#error-handling--resilience)

---

**Start here:** [README](./README.md) or pick a tutorial below!

**Choose your path:**
- 🚀 [30-min Quick Start](./01-getting-started.md)
- 🎓 [Beginner Tutorial](./tutorials/01-beginner-first-chain.md)
- 💬 [Build a Chatbot](./tutorials/02-memory-and-conversation.md)
- 🔄 [Learn Workflows](./tutorials/04-state-graphs-workflows.md)
- 🔍 [Master RAG](./tutorials/05-advanced-rag.md)
- 🚢 [Production Ready](./tutorials/06-production-patterns.md)

