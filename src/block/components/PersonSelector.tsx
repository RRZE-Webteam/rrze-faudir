import {__} from "@wordpress/i18n";
import {
  FormTokenField,
  __experimentalHeading as Heading
} from "@wordpress/components";

export interface PersonSelectorProps {
  isLoadingPosts: boolean;
  posts: any[];
  selectedPosts: number[];
  togglePostSelection: (postId: number) => void;
}

export default function PersonSelector({
    isLoadingPosts,
    posts,
    selectedPosts,
    togglePostSelection
  }: PersonSelectorProps) {
  const suggestionMap = new Map<string, number>();
  posts.forEach((post) => {
    suggestionMap.set(post.title.rendered, post.id);
  });
  const selectedTitles = posts
    .filter((post) => selectedPosts.includes(post.id))
    .map((post) => post.title.rendered);
  const suggestions = posts.map((post) => post.title.rendered);

  return (
    <>
      <Heading level={4}>{__("Select Persons", "rrze-faudir")}</Heading>
      {isLoadingPosts ? (
        <p>{__("Loading persons...", "rrze-faudir")}</p>
      ) : posts.length > 0 ? (
        <FormTokenField
          __next40pxDefaultSize
          label={__("Type to add persons", "rrze-faudir")}
          value={selectedTitles}
          suggestions={suggestions}
          onChange={(newTokens: string[]) => {
            const newIds = newTokens
              .map((token) => suggestionMap.get(token))
              .filter((id): id is number => typeof id === "number");

            newIds.forEach((id) => {
              if (!selectedPosts.includes(id)) {
                togglePostSelection(id);
              }
            });

            selectedPosts.forEach((id) => {
              if (!newIds.includes(id)) {
                togglePostSelection(id);
              }
            });
          }}
        />
      ) : (
        <p>{__("No posts available.", "rrze-faudir")}</p>
      )}
    </>
  );

}