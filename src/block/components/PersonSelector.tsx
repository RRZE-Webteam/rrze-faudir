import {__} from "@wordpress/i18n";
import {
  FormTokenField,
  __experimentalHeading as Heading,
  __experimentalSpacer as Spacer,
  Modal,
  Notice,
  Button,
  CheckboxControl,
} from "@wordpress/components";
import {useState} from "@wordpress/element";

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
  const helpText = isLoadingPosts ? __("Loading available contacts...", "rrze-faudir") : __("Select Contacts for Display.", "rrze-faudir");
  const [showModal, setShowModal] = useState(false);

  return (
    <>
      <Heading level={3}>{__("Select Persons", "rrze-faudir")}</Heading>
      <FormTokenField
        __next40pxDefaultSize
        label={__("Type to add persons", "rrze-faudir")}
        value={selectedTitles}
        suggestions={suggestions}
        disabled={isLoadingPosts || posts.length === 0}
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
      <Spacer paddingTop="0.5rem" paddingBottom="1rem">
        <Button
          variant="tertiary"
          onClick={() => setShowModal(true)}
          disabled={isLoadingPosts || posts.length === 0}
        >
          {__("Or choose by List.", "rrze-faudir")}
        </Button>
      </Spacer>
      {posts.length === 0 &&
          <Notice isDismissible={false} status="info">
            {__("There are currently no Contacts available. Start adding your first FAUdir Contacts via the WordPress Dashboard > Persons.", "rrze-faudir")}
          </Notice>
      }
      {showModal && (
        <Modal
          title={__("Select Persons", "rrze-faudir")}
          onRequestClose={() => setShowModal(false)}
        >
          <p>{__("Alternatively, select persons from the checkboxes below:", "rrze-faudir")}</p>

          {posts.map((post) => {
            const checked = selectedPosts.includes(post.id);
            return (
              <CheckboxControl
                key={post.id}
                label={post.title.rendered}
                checked={checked}
                onChange={() => togglePostSelection(post.id)}
              />
            );
          })}

          <div style={{marginTop: "1em"}}>
            <Button variant="secondary" onClick={() => setShowModal(false)}>
              {__("Close", "rrze-faudir")}
            </Button>
          </div>
        </Modal>
      )}
    </>
  );

}