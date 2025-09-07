from typing import Set
import sys
import json


def jaccard_similarity(set_a: Set[str], set_b: Set[str]) -> float:
	"""
	Compute the Jaccard Similarity between two sets of strings.

	Formula:
	  J(A, B) = |A ∩ B| / |A ∪ B|

	Edge cases:
	- If both sets are empty: return 1.0
	- If exactly one set is empty: return 0.0

	Returns:
	  A float between 0.0 and 1.0 (inclusive).
	"""
	# Both empty → perfectly similar
	if not set_a and not set_b:
		return 1.0

	# One empty, the other not → completely dissimilar
	if not set_a or not set_b:
		return 0.0

	intersection_size = len(set_a & set_b)
	union_size = len(set_a | set_b)

	# Guard against division by zero (shouldn't occur due to checks above)
	return intersection_size / union_size if union_size > 0 else 1.0


def _print_examples() -> None:
	"""Run and print example similarity calculations for quick verification."""
	# Example 1: From the CPU LILAC AwardMatch context (partial overlap)
	award_criteria = {"research publication", "international collaboration", "student mobility"}
	university_records = {"faculty research", "student exchange program", "international collaboration"}
	print("Example 1:", jaccard_similarity(award_criteria, university_records))

	# Example 2: Disjoint sets (no overlap) → 0.0
	a = {"accreditation", "community extension"}
	b = {"student housing", "scholarship grants"}
	print("Example 2:", jaccard_similarity(a, b))

	# Example 3: Both empty → 1.0
	print("Example 3:", jaccard_similarity(set(), set()))

	# Example 4: Partial overlap with multiple matches
	x = {"publication", "patent", "grant", "conference"}
	y = {"grant", "fellowship", "publication"}
	print("Example 4:", jaccard_similarity(x, y))


def _run_cli() -> int:
	"""
	CLI JSON interface.

	Input (stdin JSON):
	- {"set_a": ["..."], "set_b": ["..."]}
	  or
	- {"pairs": [{"set_a": [...], "set_b": [...]}, ...]}

	Output (stdout JSON):
	- {"similarity": float}
	  or
	- {"results": [float, ...]}
	"""
	try:
		payload_text = sys.stdin.read().strip()
		if not payload_text:
			print(json.dumps({"error": "empty_input"}))
			return 1
		payload = json.loads(payload_text)
		if "pairs" in payload:
			results = []
			for pair in payload["pairs"]:
				set_a = set(map(str, pair.get("set_a", [])))
				set_b = set(map(str, pair.get("set_b", [])))
				results.append(jaccard_similarity(set_a, set_b))
			print(json.dumps({"results": results}))
			return 0
		set_a = set(map(str, payload.get("set_a", [])))
		set_b = set(map(str, payload.get("set_b", [])))
		print(json.dumps({"similarity": jaccard_similarity(set_a, set_b)}))
		return 0
	except Exception as exc:
		print(json.dumps({"error": "exception", "message": str(exc)}))
		return 1


if __name__ == "__main__":
	# If called with flag or piped JSON, run CLI; otherwise run examples
	if not sys.stdin.isatty():
		sys.exit(_run_cli())
	_print_examples()


