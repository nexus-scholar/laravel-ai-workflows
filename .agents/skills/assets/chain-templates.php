<?php

/**
 * Laravel AI Chain Template
 * Copy and customize this template for common tasks
 */

use Nexus\Workflow\Chains\Chain;
use Nexus\Workflow\Chains\ChainFactory;
use Nexus\Workflow\Graph\State;
use Nexus\Workflow\Graph\StateGraph;
use Nexus\Workflow\Memory\CacheConversationMemory;
use Nexus\Workflow\Prompts\PromptTemplate;
use Nexus\Workflow\Retrieval\VectorStoreRetriever;
use function Laravel\Ai\agent;

// ============================================================
// TEMPLATE 1: Simple Q&A Chain
// ============================================================

// $simpleChain = Chain::make(
//     agent(instructions: 'You are a helpful assistant'),
//     PromptTemplate::from('Question: {input}'),
//     outputKey: 'answer'
// );
//
// $result = $simpleChain->run(['input' => 'What is Laravel?']);


// ============================================================
// TEMPLATE 2: Multi-Stage Pipeline
// ============================================================

// $pipeline = ChainFactory::chain(
//     agent(instructions: 'Write creatively'),
//     PromptTemplate::from('Topic: {topic}'),
//     'draft'
// )
// ->thenPrompt(
//     agent(instructions: 'Edit for clarity'),
//     PromptTemplate::from('Text: {draft}'),
//     'edited'
// )
// ->thenPrompt(
//     agent(instructions: 'Finalize'),
//     PromptTemplate::from('Text: {edited}'),
//     'final'
// )
// ->build();
//
// $result = $pipeline->run(['topic' => 'AI']);


// ============================================================
// TEMPLATE 3: Chain with Memory
// ============================================================

// $memory = new CacheConversationMemory(
//     key: "chat.$userId",
//     ttl: 86400
// );
//
// $chatChain = Chain::make(
//     agent(),
//     PromptTemplate::from('History:\n{history}\n\nNew: {input}')
// )->withMemory($memory);
//
// $chatChain->run(['input' => 'Hello']);
// $memory->add('user', 'Hello');
// $memory->add('assistant', 'response');
//
// $chatChain->run(['input' => 'Next message']);  // Sees history


// ============================================================
// TEMPLATE 4: State Graph Workflow
// ============================================================

// final class WorkflowState extends State
// {
//     public function __construct(
//         public string $input = '',
//         public string $status = 'pending',
//         public string $result = '',
//     ) {}
//
//     public function toArray(): array
//     {
//         return [
//             'input' => $this->input,
//             'status' => $this->status,
//             'result' => $this->result,
//         ];
//     }
//
//     public static function fromArray(array $data): static
//     {
//         return new self(
//             input: $data['input'] ?? '',
//             status: $data['status'] ?? 'pending',
//             result: $data['result'] ?? '',
//         );
//     }
// }
//
// $graph = new StateGraph();
//
// $graph->addNode('validate', fn($s) =>
//     $s->with(['status' => 'validating'])
// );
//
// $graph->addNode('process', fn($s) =>
//     $s->with(['result' => processData($s->input)])
// );
//
// $graph->addNode('complete', fn($s) =>
//     $s->with(['status' => 'complete'])
// );
//
// $graph->setEntryPoint('validate');
// $graph->addEdge('validate', 'process');
// $graph->addEdge('process', 'complete');
// $graph->addEdge('complete', StateGraph::END);
//
// $result = $graph->compile()->invoke(
//     new WorkflowState(input: 'data')
// );


// ============================================================
// TEMPLATE 5: RAG Chain
// ============================================================

// $ragChain = Chain::make(
//     agent(instructions: 'Answer based on context'),
//     PromptTemplate::from(
//         "Documents:\n{context}\n\n"
//         ."Question: {input}\n\n"
//         ."Answer:"
//     )
// )->withRetriever(new VectorStoreRetriever(
//     app('vector-store'),
//     topK: 5
// ));
//
// $answer = $ragChain->run(['input' => 'How do I deploy?']);


// ============================================================
// TEMPLATE 6: Conditional Workflow
// ============================================================

// $graph = new StateGraph();
//
// $graph->addNode('assess', fn($s) =>
//     $s->with(['score' => calculateScore($s->data)])
// );
//
// $graph->addNode('approve', fn($s) =>
//     $s->with(['decision' => 'APPROVED'])
// );
//
// $graph->addNode('reject', fn($s) =>
//     $s->with(['decision' => 'REJECTED'])
// );
//
// $graph->setEntryPoint('assess');
// $graph->addConditionalEdge('assess', fn($s) =>
//     $s->score > 80 ? 'approve' : 'reject'
// );
// $graph->addEdge('approve', StateGraph::END);
// $graph->addEdge('reject', StateGraph::END);
//
// $result = $graph->compile()->invoke($state);


echo "✅ Template loaded. Uncomment templates to use.\n";

