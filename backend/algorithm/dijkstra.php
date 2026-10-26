<?php
/**
 * Dijkstra's Shortest Path Algorithm
 *
 * Traffic Level Prediction System - Phase 4
 * BCA 6th Semester Academic Project
 *
 * This file contains a pure PHP implementation of Dijkstra's algorithm.
 * It has no database calls — it only works with the graph array passed to it.
 * This makes it easy to test independently and reuse from any other file.
 *
 * Graph format expected:
 *   $graph[nodeId][neighborId] = edge_cost (float)
 *
 * Example:
 *   $graph[1][2] = 0.70;   // node 1 -> node 2, cost = 0.70
 *   $graph[2][1] = 0.70;   // node 2 -> node 1, cost = 0.70 (bidirectional)
 *
 * Usage:
 *   require_once 'dijkstra.php';
 *   $result = dijkstra($graph, $startId, $endId);
 *   // $result['path'] = [1, 2, 3]     (array of node IDs)
 *   // $result['cost'] = 2.10          (total cost of the path)
 *   // $result['found'] = true/false
 */

/**
 * Run Dijkstra's shortest path algorithm on a weighted graph.
 *
 * How it works (simple explanation for BCA project):
 *   1. Start at the source node with cost 0.
 *   2. Mark all other nodes as having infinite cost (not yet reached).
 *   3. Repeatedly pick the unvisited node with the lowest known cost.
 *   4. For each neighbor of that node, check if going through this node
 *      gives a shorter path. If yes, update the neighbor's cost.
 *   5. Repeat until we reach the destination or exhaust all nodes.
 *   6. Trace back through the "previous" array to rebuild the path.
 *
 * @param  array $graph    Adjacency list: $graph[nodeId][neighborId] = cost
 * @param  int   $startId  ID of the start node (location)
 * @param  int   $endId    ID of the destination node (location)
 * @return array {
 *   'found' => bool,     — true if a path exists
 *   'path'  => int[],   — ordered list of node IDs from start to end
 *   'cost'  => float,   — total cost of the path (-1 if not found)
 * }
 */
function dijkstra(array $graph, int $startId, int $endId): array
{
    // Edge case: start and destination are the same location
    if ($startId === $endId) {
        return ['found' => true, 'path' => [$startId], 'cost' => 0.0];
    }

    // ── Step 1: Initialise distances and tracking arrays ─────────────────────

    // $dist[$nodeId] = best known cost to reach this node from start
    // Start with "infinity" (PHP_FLOAT_MAX) for all nodes
    $dist = [];

    // $prev[$nodeId] = which node we came from to reach this node on best path
    $prev = [];

    // $unvisited = set of all nodes we haven't finalised yet
    $unvisited = [];

    foreach ($graph as $nodeId => $_) {
        $dist[$nodeId]     = PHP_FLOAT_MAX; // infinity — not yet reached
        $prev[$nodeId]     = null;          // no previous node yet
        $unvisited[$nodeId] = true;
    }

    // The start node costs 0 to reach (we're already there)
    $dist[$startId] = 0.0;

    // ── Step 2: Main Dijkstra loop ────────────────────────────────────────────

    while (!empty($unvisited)) {

        // Find the unvisited node with the smallest known distance
        // This is the "greedy" step that makes Dijkstra work correctly
        $currentId = null;
        $minCost   = PHP_FLOAT_MAX;

        foreach ($unvisited as $nodeId => $_) {
            if ($dist[$nodeId] < $minCost) {
                $minCost   = $dist[$nodeId];
                $currentId = $nodeId;
            }
        }

        // If no reachable unvisited node exists, or we reached the destination
        if ($currentId === null || $currentId === $endId) {
            break;
        }

        // Mark current node as visited (remove from unvisited set)
        unset($unvisited[$currentId]);

        // ── Step 3: Relax edges from current node ─────────────────────────────
        // For each neighbor of the current node, check if the path through
        // the current node is cheaper than what we already know.

        if (!isset($graph[$currentId])) {
            continue;
        }

        foreach ($graph[$currentId] as $neighborId => $edgeCost) {

            // Skip already-visited neighbors
            if (!array_key_exists($neighborId, $unvisited)) {
                continue;
            }

            // Cost of reaching neighbor via current node
            $newCost = $dist[$currentId] + $edgeCost;

            // If this is a better (cheaper) path to neighbor, update it
            if ($newCost < $dist[$neighborId]) {
                $dist[$neighborId] = $newCost;
                $prev[$neighborId] = $currentId; // remember where we came from
            }
        }
    }

    // ── Step 4: Reconstruct the path ─────────────────────────────────────────
    // If destination was never reached (cost still infinity), no path exists
    if ($dist[$endId] === PHP_FLOAT_MAX) {
        return ['found' => false, 'path' => [], 'cost' => -1.0];
    }

    // Walk backwards from destination to start using $prev array
    $path      = [];
    $currentId = $endId;

    while ($currentId !== null) {
        array_unshift($path, $currentId); // prepend so path is start -> end
        $currentId = $prev[$currentId];
    }

    // Safety check: path should start from $startId
    if (empty($path) || $path[0] !== $startId) {
        return ['found' => false, 'path' => [], 'cost' => -1.0];
    }

    return [
        'found' => true,
        'path'  => $path,
        'cost'  => round($dist[$endId], 4),
    ];
}
