import { __ } from "@wordpress/i18n";
import {
    FormTokenField,
    __experimentalHeading as Heading,
    __experimentalSpacer as Spacer,
    Modal,
    Notice,
    Button,
    CheckboxControl,
} from "@wordpress/components";
import { useMemo, useState } from "@wordpress/element";
import { CustomPersonRESTApi } from "../faudir/types";

export interface PersonSelectorProps {
    isLoadingPosts: boolean;
    posts: CustomPersonRESTApi[];
    selectedPosts: number[];
    togglePostSelection: (postId: number) => void;
}

type PostOption = {
    id: number;
    label: string;
};

export default function PersonSelector({
    isLoadingPosts,
    posts,
    selectedPosts,
    togglePostSelection
}: PersonSelectorProps) {
    const [showModal, setShowModal] = useState(false);

    const postOptions = useMemo(function(): PostOption[] {
        const titleCounts = new Map<string, number>();

        posts.forEach(function(post) {
            const title = post?.title?.rendered?.trim() ?? "";
            const currentCount = titleCounts.get(title) ?? 0;
            titleCounts.set(title, currentCount + 1);
        });

        return posts.map(function(post) {
            const title = post?.title?.rendered?.trim() ?? "";
            const postLanguage = typeof post?.post_language === "string"
                ? post.post_language.trim()
                : "";
            const personId = typeof post?.meta?.person_id === "string"
                ? post.meta.person_id.trim()
                : "";

            const isDuplicateTitle = (titleCounts.get(title) ?? 0) > 1;

            let label = title;

            if (isDuplicateTitle) {
                const additions: string[] = [];

                if (postLanguage !== "") {
                    additions.push(postLanguage);
                }

                if (personId !== "") {
                    additions.push(personId);
                }

                if (additions.length > 0) {
                    label = `${title} [${additions.join(" | ")}]`;
                }
            }

            return {
                id: post.id,
                label,
            };
        });
    }, [posts]);

    const suggestionMap = useMemo(function() {
        const map = new Map<string, number>();

        postOptions.forEach(function(option) {
            map.set(option.label, option.id);
        });

        return map;
    }, [postOptions]);

    const selectedTitles = useMemo(function() {
        return postOptions
            .filter(function(option) {
                return selectedPosts.includes(option.id);
            })
            .map(function(option) {
                return option.label;
            });
    }, [postOptions, selectedPosts]);

    const suggestions = useMemo(function() {
        return postOptions.map(function(option) {
            return option.label;
        });
    }, [postOptions]);

    return (
        <>
            <Heading level={3}>{__("Select Persons", "rrze-faudir")}</Heading>

            <FormTokenField
                __next40pxDefaultSize
                label={__("Type to add persons", "rrze-faudir")}
                value={selectedTitles}
                suggestions={suggestions}
                disabled={isLoadingPosts || posts.length === 0}
                onChange={function(newTokens: string[]) {
                    const newIds = newTokens
                        .map(function(token) {
                            return suggestionMap.get(token);
                        })
                        .filter(function(id): id is number {
                            return typeof id === "number";
                        });

                    newIds.forEach(function(id) {
                        if (!selectedPosts.includes(id)) {
                            togglePostSelection(id);
                        }
                    });

                    selectedPosts.forEach(function(id) {
                        if (!newIds.includes(id)) {
                            togglePostSelection(id);
                        }
                    });
                }}
            />

            <Spacer paddingTop="0.5rem" paddingBottom="1rem">
                <Button
                    variant="tertiary"
                    onClick={function() {
                        setShowModal(true);
                    }}
                    disabled={isLoadingPosts || posts.length === 0}
                >
                    {__("Or choose by list.", "rrze-faudir")}
                </Button>
            </Spacer>

            {posts.length === 0 && (
                <Notice isDismissible={false} status="info">
                    {__("There are currently no contacts available. Start adding your first FAUdir contacts via the WordPress Dashboard > Persons.", "rrze-faudir")}
                </Notice>
            )}

            {showModal && (
                <Modal
                    title={__("Select Persons", "rrze-faudir")}
                    onRequestClose={function() {
                        setShowModal(false);
                    }}
                >
                    <p>
                        {__("Alternatively, select persons from the checkboxes below:", "rrze-faudir")}
                    </p>

                    {postOptions.map(function(option) {
                        const checked = selectedPosts.includes(option.id);

                        return (
                            <CheckboxControl
                                key={option.id}
                                label={option.label}
                                checked={checked}
                                onChange={function() {
                                    togglePostSelection(option.id);
                                }}
                            />
                        );
                    })}

                    <div style={{ marginTop: "1em" }}>
                        <Button
                            variant="secondary"
                            onClick={function() {
                                setShowModal(false);
                            }}
                        >
                            {__("Close", "rrze-faudir")}
                        </Button>
                    </div>
                </Modal>
            )}
        </>
    );
}